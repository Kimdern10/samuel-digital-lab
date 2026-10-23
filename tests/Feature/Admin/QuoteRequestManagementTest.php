<?php

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('quote requests are listed and can be filtered by status', function () {
    QuoteRequest::factory()->create(['name' => 'Ngozi Eze']);
    QuoteRequest::factory()->create(['name' => 'Chidi Okafor', 'status' => QuoteStatus::Won]);

    $this->get(route('admin.quotes.index'))->assertOk()->assertSee('Ngozi Eze')->assertSee('Chidi Okafor');

    Livewire::test('pages::admin.quotes.index')
        ->set('status', 'won')
        ->assertSee('Chidi Okafor')
        ->assertDontSee('Ngozi Eze');
});

test('a quote request shows its details and a WhatsApp reply link', function () {
    $quote = QuoteRequest::factory()->create(['name' => 'Ngozi Eze', 'phone' => '0803 123 4567', 'details' => 'Menu and bookings.']);

    $this->get(route('admin.quotes.show', $quote))
        ->assertOk()
        ->assertSee('Menu and bookings.')
        ->assertSee('https://wa.me/2348031234567?text=Hello%20Ngozi%20Eze', escape: false);
});

test('the admin can change the status', function () {
    $quote = QuoteRequest::factory()->create();

    Livewire::test('pages::admin.quotes.show', ['quoteRequest' => $quote])
        ->set('status', 'contacted')
        ->assertHasNoErrors();

    expect($quote->refresh()->status)->toBe(QuoteStatus::Contacted);
});

test('the status must be one of the known statuses', function () {
    $quote = QuoteRequest::factory()->create();

    Livewire::test('pages::admin.quotes.show', ['quoteRequest' => $quote])
        ->set('status', 'archived')
        ->assertHasErrors('status');

    expect($quote->refresh()->status)->toBe(QuoteStatus::New);
});

test('the admin can delete a quote request', function () {
    $quote = QuoteRequest::factory()->create();

    Livewire::test('pages::admin.quotes.show', ['quoteRequest' => $quote])
        ->call('delete')
        ->assertRedirect(route('admin.quotes.index'));

    expect(QuoteRequest::query()->count())->toBe(0);
});
