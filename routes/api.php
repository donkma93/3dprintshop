<?php

use App\Http\Controllers\Api\Admin\AuthController as ApiAdminAuthController;
use App\Http\Controllers\Api\Admin\BannerController as ApiAdminBannerController;
use App\Http\Controllers\Api\Admin\CategoryController as ApiAdminCategoryController;
use App\Http\Controllers\Api\Admin\ChatController as ApiAdminChatController;
use App\Http\Controllers\Api\Admin\DashboardController as ApiAdminDashboardController;
use App\Http\Controllers\Api\Admin\EquipmentController as ApiAdminEquipmentController;
use App\Http\Controllers\Api\Admin\MaterialController as ApiAdminMaterialController;
use App\Http\Controllers\Api\Admin\MaterialInputController as ApiAdminMaterialInputController;
use App\Http\Controllers\Api\Admin\PageController as ApiAdminPageController;
use App\Http\Controllers\Api\Admin\PostController as ApiAdminPostController;
use App\Http\Controllers\Api\Admin\ProductController as ApiAdminProductController;
use App\Http\Controllers\Api\Admin\ProductQrController as ApiAdminProductQrController;
use App\Http\Controllers\Api\Admin\SalesController as ApiAdminSalesController;
use App\Http\Controllers\Api\Admin\SettingController as ApiAdminSettingController;
use App\Http\Controllers\Api\Admin\TaxController as ApiAdminTaxController;
use App\Http\Controllers\Api\Admin\OrderRequestController as ApiAdminOrderRequestController;
use App\Http\Controllers\Api\Admin\TrashController as ApiAdminTrashController;
use App\Http\Controllers\Api\Admin\UserController as ApiAdminUserController;
use App\Http\Controllers\Api\Public\CategoryController as ApiPublicCategoryController;
use App\Http\Controllers\Api\Public\ChatController as ApiPublicChatController;
use App\Http\Controllers\Api\Public\HomeController as ApiPublicHomeController;
use App\Http\Controllers\Api\Public\OrderRequestController as ApiPublicOrderRequestController;
use App\Http\Controllers\Api\Public\PageController as ApiPublicPageController;
use App\Http\Controllers\Api\Public\PostController as ApiPublicPostController;
use App\Http\Controllers\Api\Public\ProductController as ApiPublicProductController;
use App\Http\Controllers\Api\Public\SettingController as ApiPublicSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API v1 — dùng cho app mobile / tích hợp ngoài web
| Prefix: /api/v1
| Auth admin: Bearer token (Laravel Sanctum)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public (không cần đăng nhập)
    |--------------------------------------------------------------------------
    */
    Route::get('/home', [ApiPublicHomeController::class, 'index']);
    Route::get('/settings', [ApiPublicSettingController::class, 'show']);

    Route::get('/categories', [ApiPublicCategoryController::class, 'index']);
    Route::get('/categories/{slug}', [ApiPublicCategoryController::class, 'show']);

    Route::get('/products', [ApiPublicProductController::class, 'index']);
    Route::get('/products/{slug}', [ApiPublicProductController::class, 'show']);

    Route::get('/posts', [ApiPublicPostController::class, 'index']);
    Route::get('/posts/{slug}', [ApiPublicPostController::class, 'show']);

    Route::get('/pages', [ApiPublicPageController::class, 'index']);
    Route::get('/pages/{slug}', [ApiPublicPageController::class, 'show']);

    // Đặt hàng / để lại thông tin liên hệ
    Route::post('/orders', [ApiPublicOrderRequestController::class, 'store'])
        ->middleware('throttle:12,1');

    // Guest chat (token hội thoại, không phải Sanctum)
    Route::prefix('chat')->group(function () {
        Route::get('/', [ApiPublicChatController::class, 'show']);
        Route::post('/start', [ApiPublicChatController::class, 'start']);
        Route::post('/send', [ApiPublicChatController::class, 'send']);
        Route::post('/typing', [ApiPublicChatController::class, 'typing']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin auth
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::post('/login', [ApiAdminAuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::get('/me', [ApiAdminAuthController::class, 'me']);
            Route::post('/logout', [ApiAdminAuthController::class, 'logout']);
            Route::post('/logout-all', [ApiAdminAuthController::class, 'logoutAll']);

            Route::get('/dashboard', [ApiAdminDashboardController::class, 'index']);

            Route::apiResource('categories', ApiAdminCategoryController::class);
            Route::get('products/next-sku', [ApiAdminProductController::class, 'nextSku']);
            Route::get('products/{product}/qr', [ApiAdminProductQrController::class, 'show']);
            Route::get('products/{product}/qr/download', [ApiAdminProductQrController::class, 'download']);
            Route::post('products/{product}/qr/regenerate', [ApiAdminProductQrController::class, 'regenerate']);
            Route::apiResource('products', ApiAdminProductController::class);
            Route::get('sales/lookup', [ApiAdminSalesController::class, 'lookup']);
            Route::post('sales/sell', [ApiAdminSalesController::class, 'sell']);
            Route::get('sales/history', [ApiAdminSalesController::class, 'history']);
            Route::get('sales/report', [ApiAdminSalesController::class, 'report']);
            Route::get('sales/{sale}', [ApiAdminSalesController::class, 'show']);
            Route::get('sales/{sale}/print', [ApiAdminSalesController::class, 'printData']);

            // Module chuẩn bị thuế HKD
            Route::get('tax/summary', [ApiAdminTaxController::class, 'summary']);
            Route::get('tax/periods', [ApiAdminTaxController::class, 'periods']);
            Route::get('tax/profile', [ApiAdminTaxController::class, 'profile']);
            Route::put('tax/profile', [ApiAdminTaxController::class, 'updateProfile']);
            Route::get('tax/ledger', [ApiAdminTaxController::class, 'ledger']);
            Route::post('tax/entries', [ApiAdminTaxController::class, 'storeEntry']);
            Route::put('tax/entries/{entry}', [ApiAdminTaxController::class, 'updateEntry']);
            Route::delete('tax/entries/{entry}', [ApiAdminTaxController::class, 'destroyEntry']);
            Route::post('tax/sync', [ApiAdminTaxController::class, 'sync']);
            Route::post('tax/period/close', [ApiAdminTaxController::class, 'closePeriod']);
            Route::post('tax/period/reopen', [ApiAdminTaxController::class, 'reopenPeriod']);
            Route::post('tax/period/paid', [ApiAdminTaxController::class, 'markPaid']);

            Route::apiResource('materials', ApiAdminMaterialController::class);
            Route::apiResource('material-inputs', ApiAdminMaterialInputController::class)
                ->parameters(['material-inputs' => 'materialInput']);
            Route::apiResource('equipment', ApiAdminEquipmentController::class);
            Route::apiResource('banners', ApiAdminBannerController::class);
            Route::apiResource('posts', ApiAdminPostController::class);
            Route::apiResource('pages', ApiAdminPageController::class);

            Route::get('/settings', [ApiAdminSettingController::class, 'show']);
            Route::put('/settings', [ApiAdminSettingController::class, 'update']);
            Route::post('/settings', [ApiAdminSettingController::class, 'update']); // multipart upload

            // Chat admin
            Route::get('/chat', [ApiAdminChatController::class, 'index']);
            Route::get('/chat/notifications', [ApiAdminChatController::class, 'notifications']);
            Route::post('/chat/notifications/read', [ApiAdminChatController::class, 'markNotificationsRead']);
            Route::get('/chat/{conversation}', [ApiAdminChatController::class, 'show']);
            Route::post('/chat/{conversation}/reply', [ApiAdminChatController::class, 'reply']);
            Route::post('/chat/{conversation}/typing', [ApiAdminChatController::class, 'typing']);
            Route::get('/chat/{conversation}/poll', [ApiAdminChatController::class, 'poll']);
            Route::post('/chat/{conversation}/close', [ApiAdminChatController::class, 'close']);
            Route::post('/chat/{conversation}/reopen', [ApiAdminChatController::class, 'reopen']);

            // Yêu cầu đặt hàng
            Route::get('/orders', [ApiAdminOrderRequestController::class, 'index']);
            Route::get('/orders/{order}', [ApiAdminOrderRequestController::class, 'show']);
            Route::put('/orders/{order}', [ApiAdminOrderRequestController::class, 'update']);
            Route::patch('/orders/{order}', [ApiAdminOrderRequestController::class, 'update']);
            Route::delete('/orders/{order}', [ApiAdminOrderRequestController::class, 'destroy']);

            // Thùng rác
            Route::get('/trash', [ApiAdminTrashController::class, 'index']);
            Route::post('/trash/{type}/{id}/restore', [ApiAdminTrashController::class, 'restore']);
            Route::delete('/trash/{type}/{id}', [ApiAdminTrashController::class, 'forceDelete']);
            Route::delete('/trash', [ApiAdminTrashController::class, 'empty']);

            // Người dùng & phân quyền (chỉ super_admin qua middleware permission map + users.manage)
            Route::get('/roles', [ApiAdminUserController::class, 'roles']);
            Route::apiResource('users', ApiAdminUserController::class);
        });
    });
});
