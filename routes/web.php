<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\MaterialInputController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductQrController as AdminProductQrController;
use App\Http\Controllers\Admin\SalesController as AdminSalesController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TaxController as AdminTaxController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OrderRequestController as AdminOrderRequestController;
use App\Http\Controllers\Shop\ChatController as ShopChatController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\OrderRequestController as ShopOrderRequestController;
use App\Http\Controllers\Shop\PageController as ShopPageController;
use App\Http\Controllers\Shop\PostController as ShopPostController;
use App\Http\Controllers\Shop\ProductController as ShopProductController;
use App\Http\Controllers\Shop\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO / System
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Cửa hàng (frontend)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('shop.home');
Route::get('/san-pham', [ShopProductController::class, 'index'])->name('shop.products.index');
Route::get('/san-pham/{slug}', [ShopProductController::class, 'show'])->name('shop.products.show');
Route::get('/tin-tuc', [ShopPostController::class, 'index'])->name('shop.posts.index');
Route::get('/tin-tuc/{slug}', [ShopPostController::class, 'show'])->name('shop.posts.show');
Route::get('/trang/{slug}', [ShopPageController::class, 'show'])->name('shop.pages.show');
Route::post('/dat-hang', [ShopOrderRequestController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('shop.orders.store');

/*
|--------------------------------------------------------------------------
| Chat khách hàng (guest)
|--------------------------------------------------------------------------
*/
Route::prefix('chat')->name('shop.chat.')->group(function () {
    Route::get('/', [ShopChatController::class, 'show'])->name('show');
    Route::post('/start', [ShopChatController::class, 'start'])->name('start');
    Route::post('/send', [ShopChatController::class, 'send'])->name('send');
    Route::post('/typing', [ShopChatController::class, 'typing'])->name('typing');
});

/*
|--------------------------------------------------------------------------
| Đăng nhập quản trị
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('products/next-sku', [AdminProductController::class, 'nextSku'])->name('products.next-sku');
        Route::get('products/{product}/qr', [AdminProductQrController::class, 'show'])->name('products.qr');
        Route::get('products/{product}/qr/download', [AdminProductQrController::class, 'download'])->name('products.qr.download');
        Route::post('products/{product}/qr/regenerate', [AdminProductQrController::class, 'regenerate'])->name('products.qr.regenerate');
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('materials', MaterialController::class)->except(['show']);
        Route::resource('material-inputs', MaterialInputController::class)
            ->parameters(['material-inputs' => 'materialInput'])
            ->except(['show']);
        Route::resource('equipment', EquipmentController::class)->except(['show']);

        // Bán hàng nội bộ (QR) — giảm tồn + ghi doanh thu + phiếu gửi hàng
        Route::get('sales/scan', [AdminSalesController::class, 'scan'])->name('sales.scan');
        Route::get('sales/lookup', [AdminSalesController::class, 'lookup'])->name('sales.lookup');
        Route::post('sales/sell', [AdminSalesController::class, 'sell'])->name('sales.sell');
        Route::get('sales/history', [AdminSalesController::class, 'history'])->name('sales.history');
        Route::get('sales/report', [AdminSalesController::class, 'report'])->name('sales.report');
        Route::get('sales/{sale}/print', [AdminSalesController::class, 'printSlip'])->name('sales.print');

        // Module chuẩn bị thuế HKD (riêng biệt — super_admin qua tax.manage)
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/', [AdminTaxController::class, 'index'])->name('index');
            Route::get('/profile', [AdminTaxController::class, 'profile'])->name('profile');
            Route::put('/profile', [AdminTaxController::class, 'updateProfile'])->name('profile.update');
            Route::get('/ledger', [AdminTaxController::class, 'ledger'])->name('ledger');
            Route::post('/entries', [AdminTaxController::class, 'storeEntry'])->name('entries.store');
            Route::put('/entries/{entry}', [AdminTaxController::class, 'updateEntry'])->name('entries.update');
            Route::delete('/entries/{entry}', [AdminTaxController::class, 'destroyEntry'])->name('entries.destroy');
            Route::post('/sync', [AdminTaxController::class, 'sync'])->name('sync');
            Route::get('/report', [AdminTaxController::class, 'report'])->name('report');
            Route::get('/export', [AdminTaxController::class, 'export'])->name('export');
            Route::post('/period/close', [AdminTaxController::class, 'closePeriod'])->name('period.close');
            Route::post('/period/reopen', [AdminTaxController::class, 'reopenPeriod'])->name('period.reopen');
            Route::post('/period/paid', [AdminTaxController::class, 'markPaid'])->name('period.paid');
        });

        Route::resource('banners', BannerController::class)->except(['show']);
        Route::resource('posts', AdminPostController::class)->except(['show']);
        Route::resource('pages', AdminPageController::class)->except(['show']);
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('chat', [AdminChatController::class, 'index'])->name('chat.index');
        Route::get('chat/notifications', [AdminChatController::class, 'notifications'])->name('chat.notifications');
        Route::post('chat/notifications/read', [AdminChatController::class, 'markNotificationsRead'])->name('chat.notifications.read');
        Route::get('chat/{conversation}', [AdminChatController::class, 'show'])->name('chat.show');
        Route::post('chat/{conversation}/reply', [AdminChatController::class, 'reply'])->name('chat.reply');
        Route::post('chat/{conversation}/typing', [AdminChatController::class, 'typing'])->name('chat.typing');
        Route::get('chat/{conversation}/poll', [AdminChatController::class, 'poll'])->name('chat.poll');
        Route::post('chat/{conversation}/close', [AdminChatController::class, 'close'])->name('chat.close');
        Route::post('chat/{conversation}/reopen', [AdminChatController::class, 'reopen'])->name('chat.reopen');

        Route::get('orders', [AdminOrderRequestController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderRequestController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}', [AdminOrderRequestController::class, 'update'])->name('orders.update');
        Route::delete('orders/{order}', [AdminOrderRequestController::class, 'destroy'])->name('orders.destroy');

        Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
        Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])->name('trash.restore');
        Route::delete('trash/{type}/{id}', [TrashController::class, 'forceDelete'])->name('trash.force-delete');
        Route::delete('trash', [TrashController::class, 'empty'])->name('trash.empty');

        Route::resource('users', AdminUserController::class)->except(['show']);
    });
});
