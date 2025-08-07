<?php

use App\Http\Controllers\AlfredController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SirenaController;
use App\Http\Controllers\ShortLinkController;

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
    Route::post('customers/{id}/kyc',                 [AlfredController::class, 'addKycInfo']);
    Route::post('customers/{id}/kyc/{sub}/submit',    [AlfredController::class, 'submitKyc']);
    Route::post('quotes',                             [AlfredController::class, 'createQuote']);
    Route::post('onramp',                             [AlfredController::class, 'createOnramp']);
    Route::post('offramp',                            [AlfredController::class, 'createOfframp']);
    Route::post('support',                            [AlfredController::class, 'createSupport']);
    Route::post('customers/country',                  [AlfredController::class, 'createCustomerCountry']);
});

//enpoints clientes
Route::prefix('client')->group(function () {
    Route::post('new', [ClientController::class, 'create']);
    Route::get('all', [ClientController::class, 'getClients']);
    Route::get('uuid/{uuid}', [ClientController::class, 'getClientByUuid']);
    Route::get('{phone}', [ClientController::class, 'getClientbyPhone']);
});

//Endpoint Sirena 
Route::prefix('sirena')->group(function () {
    Route::post('request-bonus', [SirenaController::class, 'requestBonus']);
   // Route::get('recharge-resume', [SirenaController::class, 'getRechargeResume']);
  //  Route::get('companies-by-province/{provinceId}', [SirenaController::class, 'getCompaniesByProvince']);
});

//Endpoint Short.io
Route::prefix('shortlink')->group(function () {
    Route::post('create', [ShortLinkController::class, 'createShortLink']);
    Route::get('info/{linkId}', [ShortLinkController::class, 'getShortLinkInfo']);
    Route::put('update/{linkId}', [ShortLinkController::class, 'updateShortLink']);
    Route::delete('delete/{linkId}', [ShortLinkController::class, 'deleteShortLink']);
});
