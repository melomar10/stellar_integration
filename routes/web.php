<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BridgeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/kyc/callback', [BridgeController::class, 'kycCallback'])
    ->name('bridge.kyc.callback');
Route::get('/tos/{id}', [BridgeController::class, 'showTos'])
    ->name('bridge.kyc.tos');

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas del admin
Route::middleware(['auth', 'role'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clients', function () {
        return view('admin.clients');
    })->name('clients');

    Route::get('/flows', function () {
        return view('admin.flows');
    })->name('flows');

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
