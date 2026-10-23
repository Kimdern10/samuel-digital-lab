<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

test('the admin can upload a logo that shows on the website and login page', function () {
    Livewire::test('pages::admin.settings')
        ->set('logo', UploadedFile::fake()->image('logo.png', 1200, 400))
        ->assertHasNoErrors();

    $path = Setting::get('logo_path');

    expect($path)->toStartWith('branding/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
    expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(600);

    $this->get(route('home'))->assertSee(Setting::logoUrl(), escape: false);

    auth()->logout();
    $this->get(route('login'))->assertSee(Setting::logoUrl(), escape: false)->assertSee('Welcome back');
});

test('uploading a new logo replaces the old file and the logo can be removed', function () {
    $component = Livewire::test('pages::admin.settings')->set('logo', UploadedFile::fake()->image('first.png'));
    $first = Setting::get('logo_path');

    $component->set('logo', UploadedFile::fake()->image('second.png'));
    $second = Setting::get('logo_path');

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $component->call('removeLogo');

    expect(Setting::logoUrl())->toBeNull();
    Storage::disk('public')->assertMissing($second);
    $this->get(route('home'))->assertSee('brand__mark', escape: false);
});

test('the logo must be a picture', function () {
    Livewire::test('pages::admin.settings')
        ->set('logo', UploadedFile::fake()->create('logo.pdf', 50, 'application/pdf'))
        ->assertHasErrors('logo');

    expect(Setting::get('logo_path'))->toBe('');
});

test('the business name can be hidden beside the logo', function () {
    Livewire::test('pages::admin.settings')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->set('showNameWithLogo', false);

    expect(Setting::get('logo_show_name'))->toBe('0');

    $this->get(route('home'))->assertDontSee('<span class="logo__name">', escape: false);
});
