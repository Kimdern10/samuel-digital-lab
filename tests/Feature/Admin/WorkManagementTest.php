<?php

use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create());
});

test('the admin can add a work with a cover and screenshots that appears on the site', function () {
    Livewire::test('pages::admin.works.form')
        ->set('title', 'Eze Catering Website')
        ->assertSet('slug', 'eze-catering-website')
        ->set('category', 'Business website')
        ->set('summary', 'Menu, bookings and deposits online.')
        ->set('highlights', "Online menu\n\nCatering bookings")
        ->set('technologies', 'Laravel, MySQL, ')
        ->set('results', [['value' => '0.8 s', 'label' => 'page load time'], ['value' => '', 'label' => 'ignored']])
        ->set('is_featured', true)
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 2400, 1500))
        ->set('newScreenshots', [UploadedFile::fake()->image('one.png', 1200, 900), UploadedFile::fake()->image('two.png', 1200, 900)])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.works.index'));

    $work = Work::query()->sole();

    expect($work)
        ->slug->toBe('eze-catering-website')
        ->is_published->toBeTrue()
        ->is_featured->toBeTrue()
        ->highlights->toBe(['Online menu', 'Catering bookings'])
        ->technologies->toBe(['Laravel', 'MySQL'])
        ->results->toBe([['value' => '0.8 s', 'label' => 'page load time']])
        ->screenshots->toHaveCount(2)
        ->cover_path->toEndWith('.webp');

    foreach ($work->imagePaths() as $path) {
        Storage::disk('public')->assertExists($path);
    }

    $this->get(route('home'))->assertSee('Eze Catering Website');
    $this->get(route('work.show', $work))->assertOk()->assertSee($work->coverUrl());
});

test('a title and a unique web address are required', function () {
    Work::factory()->create(['slug' => 'taken']);

    Livewire::test('pages::admin.works.form')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);

    Livewire::test('pages::admin.works.form')
        ->set('title', 'Another')
        ->set('slug', 'taken')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('a work cannot have more than six screenshots', function () {
    $work = Work::factory()->create(['screenshots' => array_map(fn ($i) => "works/existing-{$i}.webp", range(1, 5))]);

    Livewire::test('pages::admin.works.form', ['work' => $work])
        ->set('newScreenshots', [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')])
        ->call('save')
        ->assertHasErrors(['newScreenshots' => 'max']);
});

test('uploads must be images', function () {
    Livewire::test('pages::admin.works.form')
        ->set('title', 'Shop')
        ->set('cover', UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['cover']);

    expect(Work::query()->count())->toBe(0);
});

test('removing a screenshot or replacing the cover deletes the old files', function () {
    $component = Livewire::test('pages::admin.works.form')
        ->set('title', 'Shop')
        ->set('cover', UploadedFile::fake()->image('cover.jpg'))
        ->set('newScreenshots', [UploadedFile::fake()->image('one.png'), UploadedFile::fake()->image('two.png')])
        ->call('save');

    $work = Work::query()->sole();
    [$oldCover, $removedShot, $keptShot] = [$work->cover_path, $work->screenshots[0], $work->screenshots[1]];

    Livewire::test('pages::admin.works.form', ['work' => $work])
        ->call('removeScreenshot', 0)
        ->set('cover', UploadedFile::fake()->image('new-cover.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $work->refresh();

    expect($work->screenshots)->toBe([$keptShot])
        ->and($work->cover_path)->not->toBe($oldCover);

    Storage::disk('public')->assertMissing([$oldCover, Work::thumbPath($oldCover), $removedShot, Work::thumbPath($removedShot)]);
    Storage::disk('public')->assertExists([$keptShot, $work->cover_path]);
});

test('the admin can remove the cover picture', function () {
    Livewire::test('pages::admin.works.form')
        ->set('title', 'Shop')
        ->set('cover', UploadedFile::fake()->image('cover.jpg'))
        ->call('save');

    $work = Work::query()->sole();
    $cover = $work->cover_path;

    Livewire::test('pages::admin.works.form', ['work' => $work])
        ->call('removeCoverImage')
        ->assertSet('removeCover', true)
        ->assertSee('The cover will be deleted when you save.')
        ->call('save')
        ->assertHasNoErrors();

    expect($work->refresh()->cover_path)->toBeNull();
    Storage::disk('public')->assertMissing([$cover, Work::thumbPath($cover)]);
});

test('removing the cover can be undone before saving', function () {
    $work = Work::factory()->create(['cover_path' => 'works/cover.webp']);
    Storage::disk('public')->put('works/cover.webp', 'x');

    Livewire::test('pages::admin.works.form', ['work' => $work])
        ->call('removeCoverImage')
        ->call('keepCover')
        ->call('save')
        ->assertHasNoErrors();

    expect($work->refresh()->cover_path)->toBe('works/cover.webp');
    Storage::disk('public')->assertExists('works/cover.webp');
});

test('a newly chosen cover can be discarded before saving', function () {
    $work = Work::factory()->create(['cover_path' => 'works/cover.webp']);

    Livewire::test('pages::admin.works.form', ['work' => $work])
        ->set('cover', UploadedFile::fake()->image('wrong.jpg'))
        ->call('removeCoverImage')
        ->assertSet('cover', null)
        ->assertSet('removeCover', false)
        ->call('save');

    expect($work->refresh()->cover_path)->toBe('works/cover.webp');
});

test('the admin can unpublish and delete a work', function () {
    $work = Work::factory()->create(['cover_path' => 'works/cover.webp']);
    Storage::disk('public')->put('works/cover.webp', 'x');
    Storage::disk('public')->put('works/cover-sm.webp', 'x');

    Livewire::test('pages::admin.works.index')->call('togglePublished', $work->id);

    expect($work->refresh()->is_published)->toBeFalse();
    $this->get(route('work.show', $work))->assertNotFound();

    Livewire::test('pages::admin.works.index')->call('delete', $work->id);

    expect(Work::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing(['works/cover.webp', 'works/cover-sm.webp']);
});

test('users who are not admins cannot save works', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.works.create'))->assertForbidden();
});
