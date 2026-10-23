<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('works', 'pages::admin.works.index')->name('admin.works.index');
    Route::livewire('works/create', 'pages::admin.works.form')->name('admin.works.create');
    Route::livewire('works/{work:id}/edit', 'pages::admin.works.form')->name('admin.works.edit');

    Route::livewire('quotes', 'pages::admin.quotes.index')->name('admin.quotes.index');
    Route::livewire('quotes/{quoteRequest}', 'pages::admin.quotes.show')->name('admin.quotes.show');

    Route::livewire('services', 'pages::admin.services')->name('admin.services');
    Route::livewire('settings', 'pages::admin.settings')->name('admin.settings');
});
