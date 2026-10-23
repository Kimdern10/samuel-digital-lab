<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the home page uses the default content until the admin changes it', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('What we do')
        ->assertSee('Websites for every kind of business')
        ->assertSee('Mobile responsive')
        ->assertSee('From first chat to launch');
});

test('the admin can change the section headings and lists on the home page', function () {
    Livewire::test('pages::admin.content')
        ->set('content.services_eyebrow', 'Our services')
        ->set('content.services_title', 'Websites that bring customers')
        ->set('heroWords', "sales\n\nbookings")
        ->set('technologies', "Laravel\nVue")
        ->set('rows.features.0.title', 'Works offline')
        ->call('addRow', 'process_steps')
        ->set('rows.process_steps.4.title', 'Grow')
        ->set('rows.process_steps.4.text', 'We keep improving it with you.')
        ->call('removeRow', 'why_points', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('services_title'))->toBe('Websites that bring customers')
        ->and(Setting::list('hero_words'))->toBe(['sales', 'bookings'])
        ->and(Setting::list('process_steps'))->toHaveCount(5)
        ->and(Setting::list('why_points'))->toHaveCount(4);

    $this->get(route('home'))
        ->assertSee('Our services')
        ->assertSee('Websites that bring customers')
        ->assertSee('Works offline')
        ->assertSee('We keep improving it with you.')
        ->assertDontSee('Clean and modern design')
        ->assertSee('Vue');
});

test('main headings and list titles are required', function () {
    Livewire::test('pages::admin.content')
        ->set('content.services_title', '')
        ->set('rows.features.0.title', '')
        ->set('speedScores.0.value', 'fast')
        ->call('save')
        ->assertHasErrors(['content.services_title', 'rows.features.0.title', 'speedScores.0.value']);

    expect(Setting::query()->count())->toBe(0);
});

test('the content page cannot change the business details', function () {
    Livewire::test('pages::admin.content')
        ->set('content.email', 'attacker@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('email'))->toBe('udochukwus060@gmail.com');
});
