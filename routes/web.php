<?php

use EleganceCMS\Iyzico\Http\Controllers\IyzicoController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment/iyzico')->name('payments.iyzico.')->group(function () {
    Route::middleware(['web', 'core'])->group(function () {
        Route::post('gateway', [IyzicoController::class, 'gateway'])->name('gateway');
        Route::get('success', [IyzicoController::class, 'success'])->name('success');
        Route::get('error', [IyzicoController::class, 'error'])->name('error');
        //taksit
        Route::post('installments', [IyzicoController::class, 'getInstallments'])->name('installments');
        //kart sorgulama
        Route::post('check-bin', [IyzicoController::class, 'checkBin'])->name('check-bin');
        Route::any('check-threeds/{data}', [IyzicoController::class, 'checkThreeds'])->name('check-threeds');
    });
});