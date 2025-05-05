<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;



Route::get('/', function () {
    return view('welcome');
});


Route::get('/kyc/callback', [BridgeController::class, 'kycCallback'])
    ->name('bridge.kyc.callback');
Route::get('/tos', [BridgeController::class, 'showTos'])
    ->name('bridge.kyc.tos');


// routes/web.php
// Route::get('/crear-customer', function () {
//     return view('kyc.create-customer');
// })->name('bridge.customers.form');

// Route::post('/crear-customer', [BridgeController::class, 'createCustomer'])
//     ->name('bridge.customers.create');
