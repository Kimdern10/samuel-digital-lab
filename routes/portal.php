<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\PaystackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('portal')->group(function () {
    Route::livewire('/', 'pages::portal.home')->name('portal');
    Route::livewire('projects/{project}', 'pages::portal.project')->name('portal.projects.show');
    Route::livewire('invoices', 'pages::portal.invoices')->name('portal.invoices');
    Route::livewire('invoices/{invoice}', 'pages::portal.invoice')->name('portal.invoices.show');
    Route::post('invoices/{invoice}/pay', [PaystackController::class, 'pay'])->middleware('throttle:10,1')->name('portal.invoices.pay');
    Route::get('payments/callback', [PaystackController::class, 'callback'])->name('portal.payments.callback');
    Route::get('invoices/{invoice}/pdf', InvoicePdfController::class)->name('portal.invoices.pdf');
});

Route::post('paystack/webhook', [PaystackController::class, 'webhook'])->middleware('throttle:60,1')->name('paystack.webhook');
