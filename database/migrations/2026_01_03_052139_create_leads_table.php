<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Planes de Servicio (Tarifas)
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('reception_cost_per_box', 10, 2)->default(0); // Costo por caja recibida
            $table->decimal('picking_cost_per_order', 10, 2)->default(0); // Picking base (1 item + packing)
            $table->decimal('additional_item_cost', 10, 2)->default(0);   // Costo item adicional
            $table->decimal('premium_packing_cost', 10, 2)->default(0);   // Costo empaque premium
            $table->decimal('return_cost', 10, 2)->default(0);            // Costo por devolución
            
            // Configuración de Almacenamiento
            $table->enum('storage_billing_type', ['m3', 'bins'])->default('bins');
            $table->decimal('m3_price_monthly', 10, 2)->nullable(); // Precio si es por m3
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Precios por Tipo de Bin (para cuando el cobro es por bines)
        Schema::create('service_plan_bin_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('bin_type_id')->constrained()->onDelete('cascade');
            $table->decimal('price_per_day', 10, 2); // Precio diario por bin ocupado
            $table->timestamps();
        });

        // 3. Actualizar Acuerdos de Cliente (Vinculación con el Plan)
        Schema::table('client_billing_agreements', function (Blueprint $table) {
            // Si ya existe la tabla, agregamos las columnas, si no, créala primero.
            // Asumo que existe por tus archivos, así que agrego columnas.
            if (!Schema::hasColumn('client_billing_agreements', 'service_plan_id')) {
                $table->foreignId('service_plan_id')->nullable()->constrained('service_plans');
                $table->decimal('agreed_m3_volume', 10, 2)->nullable(); // Cantidad contratada si es m3
                $table->boolean('has_premium_packing')->default(false);
            }
        });

        // 4. Billetera Virtual (Wallet)
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        // 5. Transacciones de Billetera
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']); // Ingreso o Egreso
            $table->decimal('amount', 12, 2);
            $table->string('reference_type')->nullable(); // 'order', 'payment', 'withdrawal'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description');
            $table->decimal('fee', 10, 2)->default(0); // Para el 5% de retiro
            $table->timestamps();
        });

        // 6. Pre-Facturas (Cierre mensual)
        Schema::create('pre_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->string('period_month', 7); // YYYY-MM
            $table->enum('status', ['open', 'closed', 'invoiced'])->default('open');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        // 7. Detalles de Pre-Factura (Diario)
        Schema::create('pre_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_invoice_id')->constrained()->onDelete('cascade');
            $table->date('activity_date');
            $table->string('concept'); // 'Storage', 'Picking Order #123', 'Reception ASN #55'
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_invoice_details');
        Schema::dropIfExists('pre_invoices');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('service_plan_bin_prices');
        
        if (Schema::hasColumn('client_billing_agreements', 'service_plan_id')) {
            Schema::table('client_billing_agreements', function (Blueprint $table) {
                $table->dropForeign(['service_plan_id']);
                $table->dropColumn(['service_plan_id', 'agreed_m3_volume', 'has_premium_packing']);
            });
        }
        
        Schema::dropIfExists('service_plans');
    }
};