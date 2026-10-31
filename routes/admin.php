<?php

use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\QuoteExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('works', 'pages::admin.works.index')->name('admin.works.index');
    Route::livewire('works/create', 'pages::admin.works.form')->name('admin.works.create');
    Route::livewire('works/{work:id}/edit', 'pages::admin.works.form')->name('admin.works.edit');

    Route::livewire('blog', 'pages::admin.posts.index')->name('admin.posts.index');
    Route::livewire('blog/create', 'pages::admin.posts.form')->name('admin.posts.create');
    Route::livewire('blog/{post:id}/edit', 'pages::admin.posts.form')->name('admin.posts.edit');

    Route::livewire('quotes', 'pages::admin.quotes.index')->name('admin.quotes.index');
    Route::get('quotes/export', QuoteExportController::class)->name('admin.quotes.export');
    Route::livewire('quotes/{quoteRequest}', 'pages::admin.quotes.show')->name('admin.quotes.show');

    Route::livewire('content', 'pages::admin.content')->name('admin.content');
    Route::livewire('about', 'pages::admin.about')->name('admin.about');
    Route::livewire('services', 'pages::admin.services')->name('admin.services');
    Route::livewire('calculator', 'pages::admin.calculator')->name('admin.calculator');
    Route::livewire('testimonials', 'pages::admin.testimonials')->name('admin.testimonials');
    Route::livewire('questions', 'pages::admin.faqs')->name('admin.faqs');
    Route::livewire('settings', 'pages::admin.settings')->name('admin.settings');

    Route::livewire('newsletter', 'pages::admin.subscribers')->name('admin.subscribers');
    Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('admin.subscribers.export');
});
