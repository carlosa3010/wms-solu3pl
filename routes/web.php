<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\WarehouseManagementController;
use App\Http\Controllers\BinTypeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\RMAController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\Client\ClientPortalController;
use App\Http\Controllers\Auth\PasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes - Solu3PL WMS
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// --- AUTENTICACIÓN ---
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- RECUPERACIÓN DE CONTRASEÑA ---
Route::get('/forgot-password', [PasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update');

// --- RUTAS PROTEGIDAS (Requieren Login) ---
Route::middleware(['auth'])->group(function () {

    // Gestión de Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ==========================================
    // ÁREA ADMINISTRATIVA (admin/)
    // ==========================================
    Route::prefix('admin')->name('admin.')->group(function () {
        
        // Dashboard Principal
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        // Módulo: Comercial (Clientes & CRM)
        Route::prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/create', [ClientController::class, 'create'])->name('clients.create');
            Route::post('/', [ClientController::class, 'store'])->name('clients.store');
            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
            Route::put('/{client}', [ClientController::class, 'update'])->name('clients.update');
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
            
            Route::patch('/{client}/toggle', [ClientController::class, 'toggleStatus'])->name('clients.toggle');
            Route::patch('/{client}/reset-password', [ClientController::class, 'resetPassword'])->name('clients.reset_password');
        });

        Route::prefix('crm')->group(function () {
            Route::get('/', [LeadController::class, 'index'])->name('crm.index');
            Route::post('/', [LeadController::class, 'store'])->name('crm.store');
            Route::post('/{id}/convert', [LeadController::class, 'convertToClient'])->name('crm.convert');
        });

        // Módulo: Catálogo de Productos
        Route::resource('products', ProductController::class)->names([
            'index'   => 'products.index',
            'create'  => 'products.create',
            'store'   => 'products.store',
            'edit'    => 'products.edit',
            'update'  => 'products.update',
            'destroy' => 'products.destroy',
        ]);
        Route::get('products/import', [ProductController::class, 'importView'])->name('products.import');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import.post');

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
            'index'   => 'categories.index',
            'store'   => 'categories.store',
            'update'  => 'categories.update',
            'destroy' => 'categories.destroy',
        ]);

        // Módulo: Inventario y Stock
        Route::prefix('inventory')->group(function () {
            Route::get('/stock', [InventoryController::class, 'stock'])->name('inventory.stock');
            Route::get('/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
            Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('inventory.adjustments');
            Route::post('/adjustments', [InventoryController::class, 'storeAdjustment'])->name('inventory.adjustments.store');
            
            Route::get('/get-sources', [InventoryController::class, 'getSources'])->name('inventory.get_sources');
            Route::get('/get-bins', [InventoryController::class, 'getBins'])->name('inventory.get_bins');
            
            // Ruta principal del mapa (la que carga la vista map.blade.php)
            Route::get('/map', [WarehouseManagementController::class, 'index'])->name('inventory.map');
        });

        // Módulo: Infraestructura (Sucursales y Bodegas)
        Route::get('/branches', [WarehouseManagementController::class, 'index'])->name('branches.index');
        // Route::get('/warehouses', [WarehouseManagementController::class, 'index'])->name('warehouses.index'); // Conflictiva si usas resource abajo

        Route::get('/coverage', [WarehouseManagementController::class, 'coverage'])->name('coverage.index');
        Route::put('/branches/{id}/coverage', [WarehouseManagementController::class, 'updateCoverage'])->name('branches.coverage');
        
        // --- RUTAS DEL MAPA Y RACKS (AGREGADAS/AJUSTADAS) ---
        Route::get('warehouses/rack-details', [WarehouseManagementController::class, 'getRackDetails'])->name('warehouses.rack_details');
        Route::post('warehouses/save-rack', [WarehouseManagementController::class, 'saveRack'])->name('warehouses.save_rack');
        // ----------------------------------------------------

        // Operaciones de Sucursales y Bodegas (Manuales para evitar conflictos de resource si es necesario)
        Route::post('/branches', [WarehouseManagementController::class, 'store'])->name('branches.store'); // Usa store del controller (para Branch)
        Route::put('/branches/{branch}', [WarehouseManagementController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [WarehouseManagementController::class, 'destroy'])->name('branches.destroy');
        
        // Operaciones de Bodegas
        // Nota: Asegúrate de que WarehouseManagementController tenga un método storeWarehouse o que uses una lógica condicional en store()
        // Aquí asumimos que usas 'storeWarehouse' como definimos en el chat anterior, o 'store' si separaste controladores.
        // Mantenemos nombres estandar de resource manual:
        Route::post('/warehouses', [WarehouseManagementController::class, 'storeWarehouse'])->name('warehouses.store'); 
        Route::put('/warehouses/{id}', [WarehouseManagementController::class, 'updateWarehouse'])->name('warehouses.update'); // Asumiendo updateWarehouse existe o usar update con lógica
        Route::delete('/warehouses/{id}', [WarehouseManagementController::class, 'destroyWarehouse'])->name('warehouses.destroy');
        Route::get('/warehouses/{id}/labels', [WarehouseManagementController::class, 'printLabels'])->name('warehouses.labels'); // Nombre corregido para coincidir con map.blade.php

        // Módulo: Operaciones
        Route::prefix('receptions')->group(function () {
            Route::get('/', [ReceptionController::class, 'index'])->name('receptions.index');
            Route::get('/create', [ReceptionController::class, 'create'])->name('receptions.create');
            Route::post('/', [ReceptionController::class, 'store'])->name('receptions.store');
            Route::get('/{id}', [ReceptionController::class, 'show'])->name('receptions.show');
            Route::delete('/{id}', [ReceptionController::class, 'destroy'])->name('receptions.destroy');
            Route::get('/{id}/labels', [ReceptionController::class, 'printLabels'])->name('receptions.labels');
            
            // Acciones adicionales
            Route::post('/{asn}/receive', [ReceptionController::class, 'receiveItem'])->name('receptions.receive');
            Route::post('/{asn}/complete', [ReceptionController::class, 'complete'])->name('receptions.complete');
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
            Route::post('/', [OrderController::class, 'store'])->name('orders.store');
            Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
            Route::delete('/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
            Route::post('/{id}/allocate', [OrderController::class, 'executeAllocation'])->name('orders.allocate');
            Route::get('/{id}/picking-list', [OrderController::class, 'printPickingList'])->name('orders.picking');
            Route::post('/{id}/fulfill', [OrderController::class, 'fulfill'])->name('orders.fulfill');
        });

        Route::prefix('shipping')->group(function () {
            Route::get('/', [ShippingController::class, 'index'])->name('shipping.index');
            Route::get('/{id}/process', [ShippingController::class, 'process'])->name('shipping.process');
            Route::post('/{id}/ship', [ShippingController::class, 'ship'])->name('shipping.ship');
            Route::get('/{id}/manifest', [ShippingController::class, 'printManifest'])->name('shipping.manifest');
        });

        Route::prefix('transfers')->group(function () {
            Route::get('/', [TransferController::class, 'index'])->name('transfers.index');
            Route::get('/create', [TransferController::class, 'create'])->name('transfers.create');
            Route::post('/', [TransferController::class, 'store'])->name('transfers.store');
            Route::get('/{transfer}/manifest', [TransferController::class, 'printManifest'])->name('transfers.manifest');
            Route::get('/{transfer}/label', [TransferController::class, 'printLabel'])->name('transfers.label');
            
            // --- NUEVAS RUTAS DE PROCESAMIENTO (Ship & Receive) ---
            Route::post('/{id}/ship', [TransferController::class, 'ship'])->name('transfers.ship');
            Route::post('/{id}/receive', [TransferController::class, 'receive'])->name('transfers.receive');
        });

        Route::prefix('rma')->group(function () {
            Route::get('/', [RMAController::class, 'index'])->name('rma.index');
            Route::get('/create', [RMAController::class, 'create'])->name('rma.create');
            Route::post('/', [RMAController::class, 'store'])->name('rma.store');
            Route::get('/{id}', [RMAController::class, 'show'])->name('rma.show');
            Route::post('/{id}/process', [RMAController::class, 'process'])->name('rma.process');
        });

        // Módulo: Finanzas
        Route::prefix('billing')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('billing.index');
            
            // --- GESTIÓN DE PAGOS RECIBIDOS ---
            Route::get('/payments', [BillingController::class, 'paymentsIndex'])->name('billing.payments.index');
            Route::post('/payments/{id}/approve', [BillingController::class, 'approvePayment'])->name('billing.payments.approve');
            Route::post('/payments/{id}/reject', [BillingController::class, 'rejectPayment'])->name('billing.payments.reject');
            
            Route::get('/rates', [BillingController::class, 'rates'])->name('billing.rates');
            Route::post('/rates', [BillingController::class, 'storeProfile'])->name('billing.rates.store');
            Route::post('/rates/update', [BillingController::class, 'updateRates'])->name('billing.rates.update'); // Asegúrate que coincida
            Route::post('/assign-agreement', [BillingController::class, 'assignAgreement'])->name('billing.assign_agreement');
            Route::get('/pre-invoice/{clientId}', [BillingController::class, 'downloadPreInvoice'])->name('billing.pre_invoice');
            Route::post('/generate', [BillingController::class, 'generateInvoices'])->name('billing.generate');
            Route::get('/invoice/{invoiceId}/download', [BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');
            Route::post('/run-daily', [BillingController::class, 'runDailyBilling'])->name('billing.run_daily');
        });

        // Módulo: Configuración
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('settings.index');
            Route::post('/', [SettingController::class, 'update'])->name('settings.update');
            Route::post('/test-mail', [SettingController::class, 'sendTestMail'])->name('settings.test_mail');

            // --- MÉTODOS DE PAGO ---
            Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment_methods.index');
            Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment_methods.store');
            Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update'])->name('payment_methods.update');
            Route::patch('/payment-methods/{id}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment_methods.toggle');
            Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy'])->name('payment_methods.destroy');

            // --- MÉTODOS DE ENVÍO ---
            Route::get('/shipping-methods', [ShippingMethodController::class, 'index'])->name('shipping_methods.index');
            Route::post('/shipping-methods', [ShippingMethodController::class, 'store'])->name('shipping_methods.store');
            Route::put('/shipping-methods/{id}', [ShippingMethodController::class, 'update'])->name('shipping_methods.update');
            Route::patch('/shipping-methods/{id}/toggle', [ShippingMethodController::class, 'toggle'])->name('shipping_methods.toggle');
            Route::delete('/shipping-methods/{id}', [ShippingMethodController::class, 'destroy'])->name('shipping_methods.destroy');

            Route::get('/bins', [BinTypeController::class, 'index'])->name('bintypes.index');
            Route::post('/bins', [BinTypeController::class, 'store'])->name('bintypes.store');
            Route::delete('/bins/{id}', [BinTypeController::class, 'destroy'])->name('bintypes.destroy');
        });

        // Módulo: Usuarios
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');
        });
    });

    // ==========================================
    // PORTAL DE CLIENTES (portal/)
    // ==========================================
    Route::prefix('portal')->name('client.')->group(function () {
        Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('portal');

        // --- CATÁLOGO CLIENTE ---
        Route::prefix('catalog')->group(function () {
            Route::get('/', [ClientPortalController::class, 'catalog'])->name('catalog');
            Route::post('/store', [ClientPortalController::class, 'storeSku'])->name('catalog.store');
            Route::put('/update/{id}', [ClientPortalController::class, 'updateSku'])->name('catalog.update');
            Route::delete('/destroy/{id}', [ClientPortalController::class, 'destroySku'])->name('catalog.destroy');
        });

        // --- INVENTARIO CLIENTE ---
        Route::prefix('stock')->group(function () {
            Route::get('/', [ClientPortalController::class, 'stock'])->name('stock');
            Route::get('/export', [ClientPortalController::class, 'exportStock'])->name('stock.export');
        });

        // --- ASN CLIENTE ---
        Route::prefix('asn')->group(function () {
            Route::get('/', [ClientPortalController::class, 'asnIndex'])->name('asn.index');
            Route::get('/create', [ClientPortalController::class, 'createAsn'])->name('asn.create');
            Route::post('/', [ClientPortalController::class, 'storeAsn'])->name('asn.store');
            Route::get('/{id}', [ClientPortalController::class, 'showAsn'])->name('asn.show'); 
            
            // --- NUEVA RUTA: Impresión de etiquetas de bulto ---
            Route::get('/{id}/label', [ClientPortalController::class, 'printAsnLabels'])->name('asn.label');
        });

        // --- PEDIDOS CLIENTE ---
        Route::prefix('orders')->group(function () {
            Route::get('/', [ClientPortalController::class, 'ordersIndex'])->name('orders.index');
            Route::get('/create', [ClientPortalController::class, 'createOrder'])->name('orders.create');
            Route::post('/', [ClientPortalController::class, 'storeOrder'])->name('orders.store');
            Route::get('/{id}', [ClientPortalController::class, 'showOrder'])->name('orders.show');
            Route::get('/{id}/edit', [ClientPortalController::class, 'editOrder'])->name('orders.edit');
            Route::put('/{id}', [ClientPortalController::class, 'updateOrder'])->name('orders.update');
            Route::get('/{id}/export', [ClientPortalController::class, 'exportOrder'])->name('orders.export');
            Route::get('/{id}/pdf', [ClientPortalController::class, 'orderPdf'])->name('orders.pdf');
        });

        // --- RMA CLIENTE ---
        Route::prefix('rma')->group(function () {
            Route::get('/', [ClientPortalController::class, 'rmaIndex'])->name('rma');
            Route::get('/{id}', [ClientPortalController::class, 'rmaShow'])->name('rma.show');
            Route::post('/{id}/action', [ClientPortalController::class, 'rmaAction'])->name('rma.action');
        });

        // --- FACTURACIÓN CLIENTE ---
        Route::prefix('billing')->group(function () {
            Route::get('/', [ClientPortalController::class, 'billing'])->name('billing.index');
            Route::post('/payment', [ClientPortalController::class, 'storePayment'])->name('billing.store_payment');
            Route::get('/download-preinvoice', [ClientPortalController::class, 'downloadPreInvoice'])->name('billing.download');
        });

        // --- GEOGRAFÍA (AJAX) ---
        Route::get('/states/{countryId}', [ClientPortalController::class, 'getStatesByCountry'])->name('states.get');

        // --- ESTÁTICOS ---
        Route::get('/api-docs', [ClientPortalController::class, 'api'])->name('api');
        Route::get('/api-access', [ClientPortalController::class, 'apiAccess'])->name('api.access'); // Corregido nombre
        Route::post('/api-access/tokens', [ClientPortalController::class, 'createToken'])->name('api.tokens.create');
        Route::delete('/api-access/tokens/{token}', [ClientPortalController::class, 'deleteToken'])->name('api.tokens.delete');

        Route::get('/support', [ClientPortalController::class, 'support'])->name('support');
    });

    Route::get('/warehouse/station', [DashboardController::class, 'warehouseStation'])->name('warehouse.station');
});

