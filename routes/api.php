<?php

use App\Http\Controllers\AlfredController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('bridge')->group(function () {
    Route::post('customers',          [BridgeController::class, 'createCustomer']);
    Route::post('customers/kyc-link', [BridgeController::class, 'generateKycLink']);
    Route::post('customers/{id}/va',  [BridgeController::class, 'createVirtualAccount']);
    Route::post('transfers',          [BridgeController::class, 'createTransfer']);
    Route::post('customers/tos-links/{id}',  [BridgeController::class, 'generateTosLink']);
});
Route::prefix('alfred')->group(function () {
    Route::post('customers',                          [AlfredController::class, 'createCustomer']);
    Route::get('kyc-requirements',                    [AlfredController::class, 'kycRequirements']);
    Route::get('customers/kyc/{customerId}',          [AlfredController::class, 'getKYCSubmission']);
    Route::get('customers/kyc/verification/{id}',          [AlfredController::class, 'getKYCVerification']);
    Route::post('kyc/upload',                         [AlfredController::class, 'uploadKycFile']);
    Route::post('customers/{id}/kyc',                 [AlfredController::class, 'addKycInfo']);
    Route::post('customers/{id}/kyc/{sub}/submit',    [AlfredController::class, 'submitKyc']);
    Route::post('quotes',                             [AlfredController::class, 'createQuote']);
    Route::get('payment-methods/{customerId}',        [AlfredController::class, 'getPaymentMethods']);
    Route::post('payment-method',                     [AlfredController::class, 'createPaymentMethod']);
    Route::post('onramp',                             [AlfredController::class, 'createOnramp']);
    Route::post('offramp',                            [AlfredController::class, 'createOfframp']);
    Route::post('support',                            [AlfredController::class, 'createSupport']);
    Route::post('process-offramp',                    [AlfredController::class, 'processOfframp']);
    Route::get('customers/{email}',                    [AlfredController::class, 'GetCustomerByEmail']);
    Route::post('customers/country',                  [AlfredController::class, 'createCustomerCountry']);
});
