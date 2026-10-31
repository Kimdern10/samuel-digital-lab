<?php

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('newsletter:127.0.0.1');
});

test('every page has the newsletter sign-up', function () {
    $this->get(route('about'))->assertSeeLivewire('newsletter-form');
});

test('a visitor can subscribe', function () {
    Livewire::test('newsletter-form')
        ->set('email', ' Ngozi@Example.com ')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSet('subscribed', true)
        ->assertSee('on the list');

    expect(Subscriber::query()->sole())
        ->email->toBe('ngozi@example.com')
        ->token->toHaveLength(48);
});

test('a valid email is needed', function () {
    Livewire::test('newsletter-form')
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors('email');

    expect(Subscriber::query()->count())->toBe(0);
});

test('subscribing twice does not create a duplicate and brings back someone who left', function () {
    Subscriber::factory()->unsubscribed()->create(['email' => 'ngozi@example.com']);

    Livewire::test('newsletter-form')->set('email', 'ngozi@example.com')->call('subscribe');

    expect(Subscriber::query()->sole()->unsubscribed_at)->toBeNull();
});

test('spam bots that fill the hidden field are not added', function () {
    Livewire::test('newsletter-form')
        ->set('email', 'bot@example.com')
        ->set('website', 'http://spam.example')
        ->call('subscribe')
        ->assertSet('subscribed', true);

    expect(Subscriber::query()->count())->toBe(0);
});

test('a subscriber can unsubscribe with their private link', function () {
    $subscriber = Subscriber::factory()->create();

    $this->get(route('newsletter.unsubscribe', $subscriber->token))
        ->assertOk()
        ->assertSee("You're unsubscribed.", escape: false);

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();

    $this->get(route('newsletter.unsubscribe', 'wrong-token'))->assertNotFound();
});

test('the admin can download active subscribers as a CSV', function () {
    Subscriber::factory()->create(['email' => 'active@example.com']);
    Subscriber::factory()->unsubscribed()->create(['email' => 'left@example.com']);

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.subscribers'))->assertOk()->assertSee('active@example.com');

    $csv = $this->get(route('admin.subscribers.export'))->assertOk()->streamedContent();

    expect($csv)->toContain('active@example.com')->not->toContain('left@example.com');
});

test('only the admin can download the list', function () {
    $this->get(route('admin.subscribers.export'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.subscribers.export'))->assertForbidden();
});
