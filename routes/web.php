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
    Route::prefix('admin')->group(function () {
        
        // Dashboard Principal
        Route::get('/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');

        // Módulo: Comercial (Clientes & CRM)
        Route::prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('admin.clients.index');
            Route::get('/create', [ClientController::class, 'create'])->name('admin.clients.create');
            Route::post('/', [ClientController::class, 'store'])->name('admin.clients.store');
            Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
            Route::put('/{client}', [ClientController::class, 'update'])->name('admin.clients.update');
            Route::delete('/{client}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');
            
            Route::patch('/{client}/toggle', [ClientController::class, 'toggleStatus'])->name('admin.clients.toggle');
            Route::patch('/{client}/reset-password', [ClientController::class, 'resetPassword'])->name('admin.clients.reset_password');
        });

        Route::prefix('crm')->group(function () {
            Route::get('/', [LeadController::class, 'index'])->name('admin.crm.index');
            Route::post('/', [LeadController::class, 'store'])->name('admin.crm.store');
            Route::post('/{id}/convert', [LeadController::class, 'convertToClient'])->name('admin.crm.convert');
        });

        // Módulo: Catálogo de Productos
        Route::resource('products', ProductController::class)->names([
            'index'   => 'admin.products.index',
            'create'  => 'admin.products.create',
            'store'   => 'admin.products.store',
            'edit'    => 'admin.products.edit',
            'update'  => 'admin.products.update',
            'destroy' => 'admin.products.destroy',
        ]);

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy'])->names([
            'index'   => 'admin.categories.index',
            'store'   => 'admin.categories.store',
            'update'  => 'admin.categories.update',
            'destroy' => 'admin.categories.destroy',
        ]);

        // Módulo: Inventario y Stock
        Route::prefix('inventory')->group(function () {
            Route::get('/stock', [InventoryController::class, 'index'])->name('admin.inventory.stock');
            Route::get('/movements', [InventoryController::class, 'movements'])->name('admin.inventory.movements');
            Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('admin.inventory.adjustments');
            Route::post('/adjustments', [InventoryController::class, 'storeAdjustment'])->name('admin.inventory.adjustments.store');
            
            Route::get('/get-sources', [InventoryController::class, 'getSources'])->name('admin.inventory.get_sources');
            Route::get('/get-bins', [InventoryController::class, 'getBins'])->name('admin.inventory.get_bins');
            
            Route::get('/map', [WarehouseManagementController::class, 'index'])->name('admin.inventory.map');
        });

        // Módulo: Infraestructura (Sucursales y Bodegas)
        Route::get('/branches', [WarehouseManagementController::class, 'index'])->name('admin.branches.index');
        Route::get('/warehouses', [WarehouseManagementController::class, 'index'])->name('admin.warehouses.index');

        Route::get('/coverage', [WarehouseManagementController::class, 'coverage'])->name('admin.coverage.index');
        Route::put('/branches/{id}/coverage', [WarehouseManagementController::class, 'updateCoverage'])->name('admin.branches.coverage');
        Route::get('/rack-details', [WarehouseManagementController::class, 'getRackDetails'])->name('admin.inventory.rack_details');
        Route::post('/save-rack', [WarehouseManagementController::class, 'saveRackDetails'])->name('admin.inventory.save_rack');

        Route::post('/branches', [WarehouseManagementController::class, 'storeBranch'])->name('admin.branches.store');
        Route::put('/branches/{id}', [WarehouseManagementController::class, 'updateBranch'])->name('admin.branches.update');
        Route::delete('/branches/{id}', [WarehouseManagementController::class, 'destroyBranch'])->name('admin.branches.destroy');
        
        Route::post('/warehouses', [WarehouseManagementController::class, 'storeWarehouse'])->name('admin.warehouses.store');
        Route::put('/warehouses/{id}', [WarehouseManagementController::class, 'updateWarehouse'])->name('admin.warehouses.update');
        Route::delete('/warehouses/{id}', [WarehouseManagementController::class, 'destroyWarehouse'])->name('admin.warehouses.destroy');
        Route::get('/warehouses/{id}/labels', [WarehouseManagementController::class, 'printLabels'])->name('admin.warehouses.labels');

        // Módulo: Operaciones
        Route::prefix('receptions')->group(function () {
            Route::get('/', [ReceptionController::class, 'index'])->name('admin.receptions.index');
            Route::get('/create', [ReceptionController::class, 'create'])->name('admin.receptions.create');
            Route::post('/', [ReceptionController::class, 'store'])->name('admin.receptions.store');
            Route::get('/{id}', [ReceptionController::class, 'show'])->name('admin.receptions.show');
            Route::delete('/{id}', [ReceptionController::class, 'destroy'])->name('admin.receptions.destroy');
            Route::get('/{id}/labels', [ReceptionController::class, 'printLabels'])->name('admin.receptions.labels');
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('admin.orders.index');
            Route::get('/create', [OrderController::class, 'create'])->name('admin.orders.create');
            Route::post('/', [OrderController::class, 'store'])->name('admin.orders.store');
            Route::get('/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
            Route::delete('/{id}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');
            Route::post('/{id}/allocate', [OrderController::class, 'executeAllocation'])->name('admin.orders.allocate');
            Route::get('/{id}/picking-list', [OrderController::class, 'printPickingList'])->name('admin.orders.picking');
        });

        Route::prefix('shipping')->group(function () {
            Route::get('/', [ShippingController::class, 'index'])->name('admin.shipping.index');
            Route::get('/{id}/process', [ShippingController::class, 'process'])->name('admin.shipping.process');
            Route::post('/{id}/ship', [ShippingController::class, 'ship'])->name('admin.shipping.ship');
            Route::get('/{id}/manifest', [ShippingController::class, 'printManifest'])->name('admin.shipping.manifest');
        });

        Route::prefix('transfers')->group(function () {
            Route::get('/', [TransferController::class, 'index'])->name('admin.transfers.index');
            Route::get('/create', [TransferController::class, 'create'])->name('admin.transfers.create');
            Route::post('/', [TransferController::class, 'store'])->name('admin.transfers.store');
            Route::get('/{reference}/manifest', [TransferController::class, 'printManifest'])->name('admin.transfers.manifest');
            
            // --- NUEVAS RUTAS DE PROCESAMIENTO (Ship & Receive) ---
            Route::post('/{id}/ship', [TransferController::class, 'ship'])->name('admin.transfers.ship');
            Route::post('/{id}/receive', [TransferController::class, 'receive'])->name('admin.transfers.receive');
        });

        Route::prefix('rma')->group(function () {
            Route::get('/', [RMAController::class, 'index'])->name('admin.rma.index');
            Route::get('/create', [RMAController::class, 'create'])->name('admin.rma.create');
            Route::post('/', [RMAController::class, 'store'])->name('admin.rma.store');
            Route::get('/{id}', [RMAController::class, 'show'])->name('admin.rma.show');
            Route::post('/{id}/process', [RMAController::class, 'process'])->name('admin.rma.process');
        });

        // Módulo: Finanzas
        Route::prefix('billing')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('admin.billing.index');
            
            // --- GESTIÓN DE PAGOS RECIBIDOS ---
            Route::get('/payments', [BillingController::class, 'paymentsIndex'])->name('admin.billing.payments.index');
            Route::post('/payments/{id}/approve', [BillingController::class, 'approvePayment'])->name('admin.billing.payments.approve');
            Route::post('/payments/{id}/reject', [BillingController::class, 'rejectPayment'])->name('admin.billing.payments.reject');
            
            Route::get('/rates', [BillingController::class, 'rates'])->name('admin.billing.rates');
            Route::post('/rates', [BillingController::class, 'storeProfile'])->name('admin.billing.rates.store');
            Route::post('/assign-agreement', [BillingController::class, 'assignAgreement'])->name('admin.billing.assign_agreement');
            Route::get('/pre-invoice/{clientId}', [BillingController::class, 'downloadPreInvoice'])->name('admin.billing.pre_invoice');
            Route::get('/invoice/{invoiceId}/download', [BillingController::class, 'downloadInvoice'])->name('admin.billing.invoice.download');
            Route::post('/run-daily', [BillingController::class, 'runDailyBilling'])->name('admin.billing.run_daily');
        });

        // Módulo: Configuración
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('admin.settings.index');
            Route::post('/', [SettingController::class, 'update'])->name('admin.settings.update');
            Route::post('/test-mail', [SettingController::class, 'sendTestMail'])->name('admin.settings.test_mail');

            // --- MÉTODOS DE PAGO ---
            Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('admin.payment_methods.index');
            Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('admin.payment_methods.store');
            Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update'])->name('admin.payment_methods.update');
            Route::patch('/payment-methods/{id}/toggle', [PaymentMethodController::class, 'toggle'])->name('admin.payment_methods.toggle');
            Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy'])->name('admin.payment_methods.destroy');

            // --- MÉTODOS DE ENVÍO ---
            Route::get('/shipping-methods', [ShippingMethodController::class, 'index'])->name('admin.shipping_methods.index');
            Route::post('/shipping-methods', [ShippingMethodController::class, 'store'])->name('admin.shipping_methods.store');
            Route::put('/shipping-methods/{id}', [ShippingMethodController::class, 'update'])->name('admin.shipping_methods.update');
            Route::patch('/shipping-methods/{id}/toggle', [ShippingMethodController::class, 'toggle'])->name('admin.shipping_methods.toggle');
            Route::delete('/shipping-methods/{id}', [ShippingMethodController::class, 'destroy'])->name('admin.shipping_methods.destroy');

            Route::get('/bins', [BinTypeController::class, 'index'])->name('admin.bintypes.index');
            Route::post('/bins', [BinTypeController::class, 'store'])->name('admin.bintypes.store');
            Route::delete('/bins/{id}', [BinTypeController::class, 'destroy'])->name('admin.bintypes.destroy');
        });

        // Módulo: Usuarios
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
            Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
            Route::put('/{user}', [UserController::class, 'update'])->name('admin.users.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
            Route::patch('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset_password');
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
        Route::get('/support', [ClientPortalController::class, 'support'])->name('support');
    });

    Route::get('/warehouse/station', [DashboardController::class, 'warehouseStation'])->name('warehouse.station');
});