<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\AlfredController;

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('/alfred/kyc', [AlfredController::class, 'showKycForm'])->name('alfred.kyc.form');
Route::post('/alfred/kyc/check-phone', [AlfredController::class, 'checkPhone'])->name('alfred.kyc.check-phone');
Route::post('/alfred/kyc/create-client', [AlfredController::class, 'createClient'])->name('alfred.kyc.create-client');
Route::post('/alfred/kyc/kyc-status', [AlfredController::class, 'getKycStatus'])->name('alfred.kyc.status');
Route::post('/alfred/kyc/submit', [AlfredController::class, 'submitKycForm'])->name('alfred.kyc.submit');

Route::get('/alfred/cuentas/{phone}', [AlfredController::class, 'showBankDetailsForm'])
    ->where('phone', '[0-9]+')
    ->name('alfred.bank-details.form');
Route::get('/alfred/cuentas/{phone}/nueva', [AlfredController::class, 'showBankDetailsCreateForm'])
    ->where('phone', '[0-9]+')
    ->name('alfred.bank-details.create-form');
Route::post('/alfred/cuentas/load', [AlfredController::class, 'loadBankDetailsByPhone'])->name('alfred.bank-details.load');
Route::post('/alfred/cuentas/set-default', [AlfredController::class, 'setDefaultBankDetail'])->name('alfred.bank-details.set-default');
Route::post('/alfred/cuentas/create', [AlfredController::class, 'createBankDetail'])->name('alfred.bank-details.create');

Route::get('/kyc/callback', [BridgeController::class, 'kycCallback'])
    ->name('bridge.kyc.callback');
Route::get('/tos/{id}', [BridgeController::class, 'showTos'])
    ->name('bridge.kyc.tos');

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/', [AuthController::class, 'login']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas del admin
Route::middleware(['auth', 'role'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clients', function () {
        return view('admin.clients');
    })->name('clients');

    Route::get('/clients/export', [ClientController::class, 'exportClients'])->name('clients.export');

    Route::get('/waiting-list', function () {
        return view('admin.waiting-list');
    })->name('waiting-list');

    Route::get('/waiting-list/export', [WaitingListController::class, 'exportWaitingList'])->name('waiting-list.export');

    Route::get('/transfers', function () {
        return view('admin.transfers');
    })->name('transfers');

    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('settings');

    // Rutas de perfil del usuario autenticado
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Rutas de gestión de usuarios (solo para administradores)
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
