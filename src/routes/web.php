<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\InvitationTokenController;
use App\Http\Controllers\Admin\OptionBillingController;
use App\Http\Controllers\Admin\OptionContractController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\ResidentAdminController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TroubleCategoryController;
use App\Http\Controllers\Admin\TroubleRequestController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\InvitePageController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\PublicOptionInvoiceController;
use Illuminate\Support\Facades\Route;

Route::post('/line/webhook', [LineWebhookController::class, 'handle'])
    ->middleware('line.messaging.signature')
    ->name('line.webhook');

Route::get('/', static function () {
    return auth('admin')->check()
        ? redirect()->route('admin.properties.index')
        : redirect()->route('admin.login');
});

Route::get('/invite', [InvitePageController::class, 'show'])->name('invite.show');

Route::view('/liff', 'liff.index')->name('liff.index');
Route::view('/liff/resident-register', 'liff.resident_register')->name('liff.resident-register');
Route::view('/liff/trouble-report', 'liff.trouble_report')->name('liff.trouble-report');

Route::get('/option-invoices/{optionBilling}', [PublicOptionInvoiceController::class, 'show'])
    ->middleware('signed')
    ->name('public.option-invoices.show');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest:admin')->group(function (): void {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.post');
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/', static fn () => redirect()->route('admin.properties.index'))->name('home');

        Route::resource('properties', PropertyController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('vendors', VendorController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('residents', ResidentAdminController::class)->only(['index', 'edit', 'update']);

        Route::resource('trouble-categories', TroubleCategoryController::class)->only(['index', 'create', 'store', 'edit', 'update']);

        Route::resource('invitation-tokens', InvitationTokenController::class)->only(['index', 'create', 'store']);

        Route::get('trouble-requests', [TroubleRequestController::class, 'index'])->name('trouble-requests.index');
        Route::get('trouble-requests/{troubleRequest}/edit', [TroubleRequestController::class, 'edit'])->name('trouble-requests.edit');
        Route::post('trouble-requests/{troubleRequest}/schedule', [TroubleRequestController::class, 'schedule'])->name('trouble-requests.schedule');
        Route::post('trouble-requests/{troubleRequest}/complete', [TroubleRequestController::class, 'complete'])->name('trouble-requests.complete');
        Route::post('trouble-requests/{troubleRequest}/cancel', [TroubleRequestController::class, 'cancel'])->name('trouble-requests.cancel');

        Route::get('settings/notification-group', [SystemSettingController::class, 'edit'])->name('settings.notification-group');
        Route::put('settings/notification-group', [SystemSettingController::class, 'update'])->name('settings.notification-group.update');

        Route::resource('option-contracts', OptionContractController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('option-contracts/{option_contract}/send-demo', [OptionContractController::class, 'sendDemo'])
            ->name('option-contracts.send-demo');

        Route::post('option-billings/{optionBilling}/invoice', [OptionBillingController::class, 'attachInvoice'])->name('option-billings.invoice');
        Route::post('option-billings/{optionBilling}/confirm-paid', [OptionBillingController::class, 'confirmPaid'])->name('option-billings.confirm-paid');
    });
});
