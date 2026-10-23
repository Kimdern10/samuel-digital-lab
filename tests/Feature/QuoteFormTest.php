<?php

use App\Enums\QuoteStatus;
use App\Mail\QuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('quote-form:127.0.0.1');

    Service::factory()->create(['title' => 'Business & portfolio websites']);
});

function fillQuoteForm(): Testable
{
    return Livewire::test('quote-form')
        ->set('name', 'Ngozi Eze')
        ->set('business', 'Eze Catering Services')
        ->set('phone', '08031234567')
        ->set('email', 'ngozi@example.com')
        ->set('service', 'Business & portfolio websites')
        ->set('budget', '₦200k – ₦400k')
        ->set('details', 'I need a site where customers can see our menu and book catering.');
}

test('a visitor can send a quote request and the owner is emailed', function () {
    Setting::put(['email' => 'owner@example.com']);

    fillQuoteForm()
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSet('name', '')
        ->assertSee('Thank you! Your request has been sent.');

    $quote = QuoteRequest::query()->sole();

    expect($quote)
        ->name->toBe('Ngozi Eze')
        ->phone->toBe('08031234567')
        ->service->toBe('Business & portfolio websites')
        ->status->toBe(QuoteStatus::New);

    Mail::assertSent(QuoteRequestReceived::class, fn (QuoteRequestReceived $mail) => $mail->hasTo('owner@example.com')
        && $mail->hasReplyTo('ngozi@example.com')
        && $mail->quote->is($quote));
});

test('the required fields must be filled in', function () {
    Livewire::test('quote-form')
        ->call('submit')
        ->assertHasErrors(['name' => 'required', 'phone' => 'required', 'service' => 'required', 'budget' => 'required', 'details' => 'required'])
        ->assertSee('Please add a phone or WhatsApp number so we can reply.');

    expect(QuoteRequest::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('the phone number, service and budget must be valid', function () {
    fillQuoteForm()
        ->set('phone', 'call me')
        ->set('service', 'Hacking services')
        ->set('budget', '₦1')
        ->call('submit')
        ->assertHasErrors(['phone' => 'regex', 'service' => 'in', 'budget' => 'in']);

    expect(QuoteRequest::query()->count())->toBe(0);
});

test('a bot that fills the hidden field sees success but nothing is saved or sent', function () {
    fillQuoteForm()
        ->set('website', 'http://spam.example')
        ->call('submit')
        ->assertSet('sent', true);

    expect(QuoteRequest::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('a visitor can only send a few requests in ten minutes', function () {
    foreach (range(1, 3) as $attempt) {
        fillQuoteForm()->call('submit')->assertSet('sent', true);
    }

    fillQuoteForm()
        ->call('submit')
        ->assertHasErrors('form')
        ->assertSet('sent', false);

    expect(QuoteRequest::query()->count())->toBe(3);
});

test('the request is still saved when the email cannot be sent', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    fillQuoteForm()
        ->call('submit')
        ->assertSet('sent', true);

    expect(QuoteRequest::query()->count())->toBe(1);
});
