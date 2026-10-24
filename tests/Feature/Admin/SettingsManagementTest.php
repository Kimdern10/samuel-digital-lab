<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the business details start with the owner\'s contacts', function () {
    expect(Setting::get('whatsapp_primary'))->toBe('09060354675')
        ->and(Setting::get('whatsapp_secondary'))->toBe('07025918539')
        ->and(Setting::get('email'))->toBe('udochukwus060@gmail.com');
});

test('settings added by a later update have their defaults even when older settings are cached', function () {
    Cache::forever('site-settings', ['city' => 'Awka, Anambra State']);

    expect(Setting::get('city'))->toBe('Awka, Anambra State')
        ->and(Setting::get('about_name'))->toBe('Samuel');

    $this->get(route('about'))->assertOk();
});

test('the admin can update the business details and the site uses them', function () {
    Livewire::test('pages::admin.settings')
        ->set('settings.city', 'Owerri, Imo State')
        ->set('settings.instagram_url', 'https://instagram.com/samueldigitallab')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('city'))->toBe('Owerri, Imo State');

    $this->get(route('home'))
        ->assertSee('Owerri, Imo State')
        ->assertSee('https://instagram.com/samueldigitallab');
});

test('the contact details must be valid', function () {
    Livewire::test('pages::admin.settings')
        ->set('settings.email', 'not-an-email')
        ->set('settings.whatsapp_primary', '')
        ->set('settings.facebook_url', 'facebook')
        ->call('save')
        ->assertHasErrors(['settings.email', 'settings.whatsapp_primary', 'settings.facebook_url']);

    expect(Setting::query()->count())->toBe(0);
});
