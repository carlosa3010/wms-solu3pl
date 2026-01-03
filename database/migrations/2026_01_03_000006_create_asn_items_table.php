<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Aseguramos que la tabla 'asns' tenga los campos necesarios
        Schema::table('asns', function (Blueprint $table) {
            if (!Schema::hasColumn('asns', 'document_ref')) {
                $table->string('document_ref')->nullable()->after('tracking_number'); // Factura o Guía
            }
            if (!Schema::hasColumn('asns', 'notes')) {
                $table->text('notes')->nullable()->after('document_ref');
            }
        });

        // Creamos la tabla de items si no existe
        if (!Schema::hasTable('asn_items')) {
            Schema::create('asn_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asn_id')->constrained('asns')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products');
                
                $table->integer('expected_quantity');
                $table->integer('received_quantity')->default(0);
                
                // Estado por línea (útil para recepciones parciales)
                $table->string('status')->default('pending'); // pending, received, discrepancy
                
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('asn_items');
        
        Schema::table('asns', function (Blueprint $table) {
            $table->dropColumn(['document_ref', 'notes']);
        });
    }
};