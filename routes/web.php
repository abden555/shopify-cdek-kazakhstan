<?php

use App\Http\Controllers\Admin\CdekSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Shopify\ShopifyOAuthController;
use App\Http\Controllers\Shopify\ShopifyWebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'admin');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('shopify')->as('shopify.')->group(function (): void {
    Route::get('/install', [ShopifyOAuthController::class, 'install'])->name('install');
    Route::get('/callback', [ShopifyOAuthController::class, 'callback'])->name('callback');
    Route::get('/app', [ShopifyOAuthController::class, 'app'])->name('app');
    Route::post('/session/exchange', [ShopifyOAuthController::class, 'exchangeSession'])->middleware('shopify.session')->name('session.exchange');
    Route::post('/webhooks/app-uninstalled', [ShopifyWebhookController::class, 'uninstalled'])->name('webhooks.uninstalled');
});

Route::middleware(['auth', 'role:administrator'])->prefix('admin')->as('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/settings/cdek', [CdekSettingsController::class, 'edit'])->name('settings.cdek.edit');
    Route::put('/settings/cdek', [CdekSettingsController::class, 'update'])->name('settings.cdek.update');
});
