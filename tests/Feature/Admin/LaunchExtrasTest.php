<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('visitor statistics are added for visitors once an ID is saved, but not for the admin', function () {
    $admin = User::factory()->admin()->create();

    $this->get(route('home'))->assertDontSee('googletagmanager.com', escape: false);

    $this->actingAs($admin);
    Livewire::test('pages::admin.settings')
        ->set('analyticsId', 'g-abc123xyz')
        ->call('saveAnalytics')
        ->assertHasNoErrors();

    expect(Setting::get('google_analytics_id'))->toBe('G-ABC123XYZ');
    $this->get(route('home'))->assertDontSee('googletagmanager.com', escape: false);

    auth()->logout();
    $this->get(route('home'))->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', escape: false);
});

test('the analytics ID must look like a Google Analytics measurement ID', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')
        ->set('analyticsId', 'UA-12345')
        ->call('saveAnalytics')
        ->assertHasErrors('analyticsId');
});

test('the privacy page shows a starter policy with the business details until the admin edits it', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Nigeria Data Protection Act 2023')
        ->assertSee('udochukwus060@gmail.com');

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test('pages::admin.settings')
        ->set('privacyPolicy', "## Our promise\n\nWe never share your number.")
        ->call('savePrivacyPolicy')
        ->assertHasNoErrors();

    $this->get(route('privacy'))->assertSee('We never share your number.')->assertDontSee('Nigeria Data Protection Act 2023');
});

test('the quote form and footer link to the privacy policy', function () {
    $this->get(route('home'))->assertSee(route('privacy'), escape: false);
});

test('pages without their own picture use the share image for link previews', function () {
    $this->get(route('home'))->assertSee('<meta property="og:image" content="'.asset('brand/share.png').'">', escape: false);
});

test('the speed card can be filled with real Google PageSpeed results', function () {
    Http::fake(['www.googleapis.com/*' => Http::response(['lighthouseResult' => [
        'categories' => [
            'performance' => ['score' => 0.91],
            'accessibility' => ['score' => 0.98],
            'best-practices' => ['score' => 1],
            'seo' => ['score' => 1],
        ],
        'audits' => [
            'first-contentful-paint' => ['numericValue' => 1200],
            'largest-contentful-paint' => ['numericValue' => 2300],
            'total-byte-weight' => ['numericValue' => 512000],
        ],
    ]])]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.content')
        ->set('content.speed_card_url', 'briefmediablog.com')
        ->call('fetchSpeedScores')
        ->assertHasNoErrors()
        ->assertSet('speedScores.0.value', '91')
        ->assertSet('rows.stats.1.value', '2.3 s')
        ->assertSet('rows.stats.2.value', '500 KB')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'url=https%3A%2F%2Fbriefmediablog.com'));

    $this->get(route('home'))->assertSee('briefmediablog.com')->assertSee('2.3 s');
});

test('a clear message is shown when Google cannot test the website', function () {
    Http::fake(['www.googleapis.com/*' => Http::response(['error' => ['message' => 'Lighthouse returned error: NO_FCP.']], 500)]);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.content')
        ->set('content.speed_card_url', 'not-a-real-site.ng')
        ->call('fetchSpeedScores')
        ->assertHasErrors('content.speed_card_url');
});

test('the admin is told to add an API key when Google refuses requests without one', function () {
    Http::fake(['www.googleapis.com/*' => Http::response(['error' => ['message' => "Quota exceeded for quota metric 'Queries'"]], 429)]);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.content')
        ->set('content.speed_card_url', 'briefmediablog.com')
        ->call('fetchSpeedScores')
        ->assertSee('PAGESPEED_API_KEY');
});

test('the dashboard shows a launch checklist of what is still missing', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertSee('Launch checklist')
        ->assertSee('Turn on two-step login')
        ->assertSee('Add a testimonial');
});
