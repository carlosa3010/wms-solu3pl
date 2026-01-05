<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientBillingAgreement;
use App\Models\PreInvoice;
use App\Models\PreInvoiceDetail;
use App\Models\Order;
use App\Models\ASN;
use App\Models\RMA;
use App\Models\Location;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BillingService
{
    /**
     * Proceso Nocturno: Calcula costos del día para todos los clientes
     */
    public function calculateDailyCosts($date = null)
    {
        $processDate = $date ? Carbon::parse($date) : Carbon::yesterday();
        $clients = Client::where('is_active', true)->get();

        foreach ($clients as $client) {
            $this->processClientDailyCost($client, $processDate);
        }
    }

    public function processClientDailyCost(Client $client, Carbon $date)
    {
        $agreement = ClientBillingAgreement::where('client_id', $client->id)->first();
        if (!$agreement || !$agreement->servicePlan) return; // Sin plan, no cobramos

        $plan = $agreement->servicePlan;
        
        // Buscar o Crear Pre-Factura del Mes
        $period = $date->format('Y-m');
        $preInvoice = PreInvoice::firstOrCreate(
            ['client_id' => $client->id, 'period_month' => $period, 'status' => 'open']
        );

        DB::beginTransaction();
        try {
            // 1. ALMACENAMIENTO
            if ($plan->storage_billing_type === 'bins') {
                // Contar bines ocupados por tipo (si quantity > 0)
                // Nota: Esto asume que Location tiene 'client_id' o 'inventory.client_id'
                // Ajustamos query según tu estructura: Inventory -> Product -> Client
                $occupiedBins = Location::whereHas('inventory', function($q) use ($client) {
                        $q->whereHas('product', function($p) use ($client) {
                            $p->where('client_id', $client->id);
                        })->where('quantity', '>', 0);
                    })
                    ->select('bin_type_id', DB::raw('count(*) as total'))
                    ->groupBy('bin_type_id')
                    ->get();

                foreach ($occupiedBins as $binData) {
                    $priceConfig = $plan->binPrices->where('bin_type_id', $binData->bin_type_id)->first();
                    $price = $priceConfig ? $priceConfig->price_per_day : 0;
                    
                    if ($price > 0) {
                        $this->addCharge($preInvoice, $date, "Almacenamiento (Bines Tipo {$binData->bin_type_id})", $binData->total, $price, 'storage');
                    }
                }
            } elseif ($plan->storage_billing_type === 'm3') {
                // Cobrar solo el día 1 del mes
                if ($date->day === 1) {
                    $m3 = $agreement->agreed_m3_volume ?? 0;
                    $price = $plan->m3_price_monthly ?? 0;
                    if ($m3 > 0 && $price > 0) {
                        $this->addCharge($preInvoice, $date, "Almacenamiento Mensual ({$m3} m3)", 1, $m3 * $price, 'storage_monthly');
                    }
                }
            }

            // 2. PEDIDOS (Picking & Packing)
            $orders = Order::where('client_id', $client->id)
                ->whereDate('processed_at', $date) // Asumiendo campo processed_at
                ->withCount('items') // Asumiendo relación items
                ->get();

            foreach ($orders as $order) {
                // Costo Base (Picking 1 item + packing)
                $this->addCharge($preInvoice, $date, "Picking Pedido #{$order->order_number}", 1, $plan->picking_cost_per_order, 'order', $order->id);

                // Items Adicionales
                $extraItems = max(0, $order->items_count - 1);
                if ($extraItems > 0 && $plan->additional_item_cost > 0) {
                    $this->addCharge($preInvoice, $date, "Items Adicionales Pedido #{$order->order_number}", $extraItems, $plan->additional_item_cost, 'order_item', $order->id);
                }

                // Empaque Premium
                if ($agreement->has_premium_packing && $plan->premium_packing_cost > 0) {
                    $this->addCharge($preInvoice, $date, "Empaque Premium #{$order->order_number}", 1, $plan->premium_packing_cost, 'premium_packing', $order->id);
                }
            }

            // 3. RECEPCIONES (ASNs)
            $receptions = ASN::where('client_id', $client->id)
                ->whereDate('received_at', $date)
                ->get(); // Asumiendo campo received_at

            foreach ($receptions as $asn) {
                // Asumimos que tienes un campo 'received_boxes' o calculamos sumando items
                $boxes = $asn->received_boxes ?? 1; 
                if ($plan->reception_cost_per_box > 0) {
                    $this->addCharge($preInvoice, $date, "Recepción ASN #{$asn->asn_number}", $boxes, $plan->reception_cost_per_box, 'reception', $asn->id);
                }
            }

            // 4. DEVOLUCIONES (RMAs)
            $rmas = RMA::where('client_id', $client->id)
                ->whereDate('processed_at', $date)
                ->get();

            foreach ($rmas as $rma) {
                if ($plan->return_cost > 0) {
                    $this->addCharge($preInvoice, $date, "Proceso Devolución #{$rma->id}", 1, $plan->return_cost, 'rma', $rma->id);
                }
            }

            // Actualizar total
            $preInvoice->total_amount = $preInvoice->details()->sum('total_price');
            $preInvoice->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // Log error
        }
    }

    private function addCharge($preInvoice, $date, $concept, $qty, $unitPrice, $refType = null, $refId = null)
    {
        PreInvoiceDetail::create([
            'pre_invoice_id' => $preInvoice->id,
            'activity_date' => $date,
            'concept' => $concept,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => $qty * $unitPrice,
            'reference_type' => $refType,
            'reference_id' => $refId
        ]);
    }

    // --- GESTIÓN DE BILLETERA (WALLET) ---

    public function getWallet($clientId)
    {
        return Wallet::firstOrCreate(['client_id' => $clientId]);
    }

    public function addFunds($clientId, $amount, $description, $refType = null, $refId = null)
    {
        $wallet = $this->getWallet($clientId);
        
        DB::transaction(function() use ($wallet, $amount, $description, $refType, $refId) {
            $wallet->balance += $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'description' => $description,
                'reference_type' => $refType,
                'reference_id' => $refId
            ]);
        });
    }

    public function chargeWalletForShipping($clientId, $amount, $orderId)
    {
        $wallet = $this->getWallet($clientId);

        if ($wallet->balance < $amount) {
            throw new Exception("Saldo insuficiente en billetera para envío.");
        }

        DB::transaction(function() use ($wallet, $amount, $orderId) {
            $wallet->balance -= $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'description' => "Cobro envío Orden #{$orderId}",
                'reference_type' => 'order_shipping',
                'reference_id' => $orderId
            ]);
        });
    }

    public function requestWithdrawal($clientId, $amount)
    {
        $wallet = $this->getWallet($clientId);
        $fee = $amount * 0.05; // 5% fee
        $totalDeduction = $amount + $fee; // O $amount si el fee está incluido. Asumimos que se descuenta aparte o incluido.
        // Lógica: Si pides 100, te descontamos 100 del saldo, pero te transferimos 95.
        // O: Si pides retirar 100, validamos que tengas 100, te descontamos 100, y registramos que 5 son fee.
        
        if ($wallet->balance < $amount) {
            throw new Exception("Saldo insuficiente.");
        }

        DB::transaction(function() use ($wallet, $amount, $fee) {
            $wallet->balance -= $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'fee' => $fee,
                'description' => "Solicitud retiro de fondos (Fee 5%: {$fee})",
                'reference_type' => 'withdrawal',
            ]);
        });

        return $fee;
    }
}