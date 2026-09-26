<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ContentPageController as AdminContentPageController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

// Public storefront and localized content routes.
Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::redirect('/products/detail', '/products/studio-wireless-headphones');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/about-us', fn () => app(ContentPageController::class)->show('about-us'))->name('about');
Route::get('/privacy-policy', fn () => app(ContentPageController::class)->show('privacy-policy'))->name('privacy');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
// Stream private chat media only to its customer or an active administrator.
Route::get('/chat/attachments/{message}', ChatAttachmentController::class)->middleware('auth:web,admin')->name('chat.attachments.show');

// ================================= Frontend Users =================================

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    // Restrict social authentication to providers implemented by TrendShop.
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->whereIn('provider', ['google', 'telegram'])->middleware('throttle:10,1')->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->whereIn('provider', ['google', 'telegram'])->middleware('throttle:10,1')->name('social.callback');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

// Customer account, shopping and checkout routes.
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profile/addresses', [ProfileController::class, 'storeAddress'])->name('addresses.store');
    Route::patch('/profile/addresses/{address}', [ProfileController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/profile/addresses/{address}', [ProfileController::class, 'destroyAddress'])->name('addresses.destroy');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    // Keep customer support chat protected by the storefront session.
    Route::get('/contact', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/contact/messages', [ChatController::class, 'messages'])->name('chat.messages.index');
    Route::post('/contact/messages', [ChatController::class, 'store'])->middleware('throttle:30,1')->name('chat.messages.store');
});

// ================================= Dashbaord admin =================================

// Keep administration under its private path and independent session guard.
Route::prefix('admin/phyron/100203/trend_shop')->name('admin.')->group(function (): void {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->middleware('auth:admin')->name('logout');

    Route::middleware(['auth:admin', 'admin'])->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        // Scope image deletion to its parent product before the controller is called.
        Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->scopeBindings()->name('products.images.destroy');
        Route::resource('products', AdminProductController::class)->except('show');
        Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/label', [AdminOrderController::class, 'label'])->name('orders.label');
        Route::patch('orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
        // Manage administrator and customer accounts from one protected module.
        Route::resource('customers', AdminCustomerController::class)->except('show');
        Route::get('content', [AdminContentPageController::class, 'index'])->name('content.index');
        Route::get('content/{page}/edit', [AdminContentPageController::class, 'edit'])->name('content.edit');
        Route::put('content/{page}', [AdminContentPageController::class, 'update'])->name('content.update');
        Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/open', [AdminNotificationController::class, 'open'])->name('notifications.open');
        Route::patch('notifications/{notification}/read', [AdminNotificationController::class, 'read'])->name('notifications.read');
        // Let administrators manage every customer support conversation from one inbox.
        Route::get('messages', [AdminChatController::class, 'index'])->name('chat.index');
        Route::get('messages/conversations', [AdminChatController::class, 'conversations'])->name('chat.conversations.index');
        Route::get('messages/{conversation}', [AdminChatController::class, 'messages'])->name('chat.messages.index');
        Route::post('messages/{conversation}', [AdminChatController::class, 'store'])->middleware('throttle:60,1')->name('chat.messages.store');
    });
});
