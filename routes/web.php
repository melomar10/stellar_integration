<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;



Route::get('/', function () {
    return view('welcome');
});


Route::get('/kyc/callback', [BridgeController::class, 'kycCallback'])
    ->name('bridge.kyc.callback');
Route::get('/tos/{id}', [BridgeController::class, 'showTos'])
    ->name('bridge.kyc.tos');

Route::get('/admin', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::get('/admin/clients', function () {
    return view('admin.clients');
})->name('admin.clients');

Route::get('/admin/flows', function () {
    return view('admin.flows');
})->name('admin.flows');

Route::get('/admin/transfers', function () {
    return view('admin.transfers');
})->name('admin.transfers');

Route::get('/admin/settings', function () {
    return view('admin.settings');
})->name('admin.settings');
