<?php

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

Route::post('bridge/customers',          [BridgeController::class, 'createCustomer']);
Route::post('bridge/customers/kyc-link', [BridgeController::class, 'generateKycLink']);
Route::post('bridge/customers/{id}/va',  [BridgeController::class, 'createVirtualAccount']);
Route::post('bridge/transfers',          [BridgeController::class, 'createTransfer']);
Route::post('bridge/customers/tos-links',  [BridgeController::class, 'generateTosLink']);
