<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CurrentResidentController;
use App\Http\Controllers\Api\InviteRegisterController;
use App\Http\Controllers\Api\OptionBillingPaidController;
use App\Http\Controllers\Api\PropertyListController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\TroubleCategoryListController;
use App\Http\Controllers\Api\TroubleMediaUploadSignatureController;
use App\Http\Controllers\Api\TroubleRequestStoreController;
use App\Http\Controllers\Api\VendorAvailabilityController;
use App\Http\Controllers\Api\VendorContactListController;
use Illuminate\Support\Facades\Route;

Route::get('/properties', PropertyListController::class)->name('api.properties.index');

Route::get('/trouble-categories', TroubleCategoryListController::class)->name('api.trouble-categories.index');

Route::get('/vendors/availability', VendorAvailabilityController::class)->name('api.vendors.availability');

Route::get('/vendors/contact-list', VendorContactListController::class)
    ->middleware('line.liff')
    ->name('api.vendors.contact-list');

Route::get('/me', CurrentResidentController::class)
    ->middleware('line.liff')
    ->name('api.me');

Route::post('/residents', [ResidentController::class, 'store'])
    ->middleware('line.liff')
    ->name('api.residents.store');

Route::put('/residents', [ResidentController::class, 'update'])
    ->middleware('line.liff')
    ->name('api.residents.update');

Route::post('/trouble-requests', TroubleRequestStoreController::class)
    ->middleware('line.liff')
    ->name('api.trouble-requests.store');

Route::get('/media/trouble-upload-signature', TroubleMediaUploadSignatureController::class)
    ->middleware('line.liff')
    ->name('api.media.trouble-upload-signature');

Route::post('/invite/register', InviteRegisterController::class)
    ->middleware('line.liff')
    ->name('api.invite.register');

Route::post('/option-billings/{optionBilling}/paid', OptionBillingPaidController::class)
    ->middleware('line.liff')
    ->name('api.option-billings.paid');
