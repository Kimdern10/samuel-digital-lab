<?php

use App\Models\Setting;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('the home page and about page show the starter story until it is edited', function () {
    $this->get(route('home'))
        ->assertSee('The person behind your website')
        ->assertSee("Hi, I'm Samuel")
        ->assertSee(route('about'), escape: false);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Founder &amp; Lead Developer', escape: false)
        ->assertSee('"@type":"Person"', escape: false)
        ->assertSee('Laravel');
});

test('the admin can edit their story, facts and skills', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.about')
        ->set('about.about_name', 'Samuel Udochukwu')
        ->set('about.about_story', "I build websites in Awka.\n\nI reply on WhatsApp the same day.")
        ->call('addFact')
        ->set('facts.0.value', '4+')
        ->set('facts.0.label', 'years building websites')
        ->set('skills', "Laravel\nFigma")
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('about_name'))->toBe('Samuel Udochukwu')
        ->and(Setting::list('about_facts'))->toBe([['value' => '4+', 'label' => 'years building websites']])
        ->and(Setting::list('about_skills'))->toBe(['Laravel', 'Figma']);

    $this->get(route('about'))
        ->assertSee('Samuel Udochukwu')
        ->assertSee('I reply on WhatsApp the same day.')
        ->assertSee('years building websites')
        ->assertSee('Figma');
});

test('a name and a story are required, and every fact needs a number and a meaning', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.about')
        ->set('about.about_name', '')
        ->set('about.about_story', '')
        ->call('addFact')
        ->call('save')
        ->assertHasErrors(['about.about_name', 'about.about_story', 'facts.0.value', 'facts.0.label']);
});

test('the admin can upload, replace and remove their photo', function () {
    $this->actingAs(User::factory()->admin()->create());

    $component = Livewire::test('pages::admin.about')->set('photo', UploadedFile::fake()->image('me.jpg', 900, 1200));
    $first = Setting::get('about_photo_path');

    Storage::disk('public')->assertExists([$first, Work::thumbPath($first)]);
    $this->get(route('home'))->assertSee(Storage::disk('public')->url(Work::thumbPath($first)), escape: false);

    $component->set('photo', UploadedFile::fake()->image('new.jpg', 900, 1200));
    $second = Setting::get('about_photo_path');

    Storage::disk('public')->assertMissing([$first, Work::thumbPath($first)]);

    $component->call('removeFile', 'about_photo_path');

    expect(Setting::get('about_photo_path'))->toBe('');
    Storage::disk('public')->assertMissing([$second, Work::thumbPath($second)]);
});

test('the photo must be a picture and the CV must be a PDF', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.about')
        ->set('photo', UploadedFile::fake()->create('me.pdf', 50, 'application/pdf'))
        ->assertHasErrors('photo')
        ->set('cv', UploadedFile::fake()->image('cv.png'))
        ->assertHasErrors('cv');

    expect(Setting::get('about_photo_path'))->toBe('')
        ->and(Setting::get('about_cv_path'))->toBe('');
});

test('an uploaded CV can be downloaded from the about page', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.about')->set('cv', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));

    $path = Setting::get('about_cv_path');

    expect($path)->toStartWith('branding/')->toEndWith('.pdf');
    Storage::disk('public')->assertExists($path);
    $this->get(route('about'))->assertSee('Download my CV (PDF)');
});

test('the about page cannot be edited by users who are not admins', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.about'))->assertForbidden();
});
