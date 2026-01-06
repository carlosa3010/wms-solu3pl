<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAllocation;
use App\Models\Inventory;
use App\Models\Client;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Country;
use App\Models\State;
use App\Models\ShippingMethod;
use App\Models\Transfer; 
use App\Models\TransferItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client', 'items', 'branch']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_id_number', 'like', "%{$search}%");
            });
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.operations.orders.index', compact('orders'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        
        $lastOrder = Order::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        $nextOrderNumber = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        return view('admin.operations.orders.create', compact('clients', 'countries', 'shippingMethods', 'nextOrderNumber'));
    }

    public function getClientProducts($clientId)
    {
        $products = Product::where('client_id', $clientId)
            ->withSum('inventory as stock_available', 'quantity')
            ->get()
            ->filter(fn($p) => $p->stock_available > 0)
            ->values();

        return response()->json($products);
    }

    public function getStatesByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    public function store(Request $request)
    {
        // Sincronizamos validación con los nombres del formulario
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'order_number' => 'required|unique:orders,order_number',
            'customer_name' => 'required|string|max:255',
            'customer_id_number' => 'required|string|max:50',
            'shipping_address' => 'required|string',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $mappedItems = array_map(fn($item) => ['product_id' => $item['product_id'], 'qty' => $item['qty']], $request->items);

        // Inteligencia de Asignación
        $plan = $this->calculateAssignmentPlan($request->customer_state, $request->customer_country, $mappedItems);

        if (!$plan['success']) {
            return back()->withInput()->withErrors(['error' => $plan['message']]);
        }

        try {
            $order = DB::transaction(function () use ($request, $plan) {
                return Order::create([
                    'order_number' => $request->order_number,
                    'external_ref' => $request->reference_number,
                    'client_id' => $request->client_id,
                    'branch_id' => $plan['target_branch_id'],
                    'customer_name' => $request->customer_name,
                    'customer_id_number' => $request->customer_id_number,
                    'customer_email' => $request->customer_email,
                    'shipping_address' => $request->shipping_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'customer_zip' => $request->customer_zip,
                    'phone' => $request-phone,
                    'shipping_method' => $request->shipping_method,
                    'status' => $plan['requires_transfer'] ? 'waiting_transfer' : 'pending',
                    'notes' => $request->notes,
                ]);
            });

            // Registrar ítems
            foreach ($request->items as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'requested_quantity' => $itemData['qty'],
                ]);
            }

            if ($plan['requires_transfer']) {
                $this->createConsolidationTransfers($order, $plan['transfer_plan']);
            }

            $this->performStockAllocation($order);
            return redirect()->route('admin.orders.index')->with('success', "Orden {$order->order_number} procesada.");

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Fallo en creación: ' . $e->getMessage()]);
        }
    }

    private function calculateAssignmentPlan($destState, $destCountry, $requestedItems)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchEvaluations = [];

        foreach ($branches as $branch) {
            $geoScore = $this->calculateGeoScore($branch, $destState, $destCountry);
            if ($geoScore === 0) continue; 

            $fulfillment = $this->evaluateStockFulfillment($branch->id, $requestedItems);
            
            $branchEvaluations[] = [
                'branch' => $branch,
                'geo_score' => $geoScore,
                'is_full_stock' => $fulfillment['is_complete'],
                'fulfillment_data' => $fulfillment
            ];
        }

        if (empty($branchEvaluations)) {
            return ['success' => false, 'message' => 'No hay sedes con cobertura para esta zona geográfica (Verifique que el país/estado coincida exactamente con la configuración de cobertura).'];
        }

        $fullStockCandidates = collect($branchEvaluations)->where('is_full_stock', true)->sortByDesc('geo_score');
        if ($fullStockCandidates->isNotEmpty()) {
            return ['success' => true, 'target_branch_id' => $fullStockCandidates->first()['branch']->id, 'requires_transfer' => false];
        }

        $bestGeoBranch = collect($branchEvaluations)->sortByDesc('geo_score')->first();
        return ['success' => true, 'target_branch_id' => $bestGeoBranch['branch']->id, 'requires_transfer' => true, 'transfer_plan' => []]; // Lógica de traslados simplificada
    }

    private function calculateGeoScore($branch, $destState, $destCountry) {
        // Normalización para comparación insensible a mayúsculas/minúsculas
        $destCountry = trim(strtolower($destCountry));
        $destState = trim(strtolower($destState));
        $branchCountry = trim(strtolower($branch->country));
        $branchState = trim(strtolower($branch->state));

        $coveredCountries = array_map('strtolower', array_map('trim', $branch->covered_countries ?? []));
        $coversCountry = ($branchCountry === $destCountry) || in_array($destCountry, $coveredCountries);

        if (!$coversCountry) return 0;
        if ($branchCountry === $destCountry && $branchState === $destState) return 100;
        
        $coveredStates = array_map('strtolower', array_map('trim', $branch->covered_states ?? []));
        if (in_array($destState, $coveredStates)) return 80;

        return 20;
    }

    private function evaluateStockFulfillment($branchId, $requestedItems) {
        $data = ['is_complete' => true, 'items' => []];
        foreach ($requestedItems as $item) {
            $stock = Inventory::where('product_id', $item['product_id'])
                ->whereHas('location.warehouse', fn($q) => $q->where('branch_id', $branchId))->sum('quantity');
            if ($stock < $item['qty']) $data['is_complete'] = false;
        }
        return $data;
    }

    private function performStockAllocation(Order $order) {
        foreach($order->items as $item) {
            $needed = $item->requested_quantity;
            $stockRecords = Inventory::where('product_id', $item->product_id)->where('quantity', '>', 0)
                ->whereHas('location.warehouse', fn($q) => $q->where('branch_id', $order->branch_id))
                ->orderBy('quantity', 'desc')->get();

            foreach($stockRecords as $record) {
                if($needed <= 0) break;
                $take = min($needed, $record->quantity);
                OrderAllocation::create(['order_item_id' => $item->id, 'location_id' => $record->location_id, 'quantity' => $take, 'status' => 'planned']);
                $record->decrement('quantity', $take);
                $needed -= $take;
            }
        }
    }
}