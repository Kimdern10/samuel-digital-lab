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

test('the admin can save private notes and a follow-up date', function () {
    $quote = QuoteRequest::factory()->create();

    Livewire::test('pages::admin.quotes.show', ['quoteRequest' => $quote])
        ->set('notes', 'Wants 5 pages and Paystack.')
        ->call('remindIn', 3)
        ->assertHasNoErrors();

    $quote->refresh();

    expect($quote->notes)->toBe('Wants 5 pages and Paystack.')
        ->and($quote->follow_up_on?->toDateString())->toBe(now()->addDays(3)->toDateString());
});

test('the dashboard lists open requests whose follow-up date has arrived', function () {
    QuoteRequest::factory()->create(['name' => 'Due Today', 'follow_up_on' => today()]);
    QuoteRequest::factory()->create(['name' => 'Overdue Client', 'follow_up_on' => today()->subDays(2), 'status' => QuoteStatus::Contacted]);
    QuoteRequest::factory()->create(['name' => 'Next Week', 'follow_up_on' => today()->addWeek()]);
    QuoteRequest::factory()->create(['name' => 'Already Won', 'follow_up_on' => today(), 'status' => QuoteStatus::Won]);

    $this->get(route('dashboard'))->assertSee('data-test="follow-ups"', escape: false);

    $names = Livewire::test('pages::admin.dashboard')->instance()->followUps->pluck('name')->all();

    expect($names)->toBe(['Overdue Client', 'Due Today']);
});

test('quote requests can be downloaded as a CSV, optionally by status', function () {
    QuoteRequest::factory()->create(['name' => 'Ngozi Eze', 'notes' => 'Call back Friday']);
    QuoteRequest::factory()->create(['name' => 'Chidi Okafor', 'status' => QuoteStatus::Won]);

    $all = $this->get(route('admin.quotes.export'))->assertOk()->streamedContent();
    $won = $this->get(route('admin.quotes.export', ['status' => 'won']))->streamedContent();

    expect($all)->toContain('Ngozi Eze')->toContain('Call back Friday')->toContain('Chidi Okafor')
        ->and($won)->toContain('Chidi Okafor')->not->toContain('Ngozi Eze');
});
