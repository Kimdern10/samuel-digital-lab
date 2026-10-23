<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\PostSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

test('the admin can write and publish an article with a cover', function () {
    Livewire::test('pages::admin.posts.form')
        ->set('title', 'Why your shop needs a website')
        ->assertSet('slug', 'why-your-shop-needs-a-website')
        ->set('body', "## It works while you sleep\n\nCustomers can order at night.")
        ->set('is_published', true)
        ->set('cover', UploadedFile::fake()->image('cover.jpg', 1600, 900))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.posts.index'));

    $post = Post::query()->sole();

    expect($post->is_published)->toBeTrue()
        ->and($post->published_at)->not->toBeNull();
    Storage::disk('public')->assertExists([$post->cover_path, Work::thumbPath($post->cover_path)]);

    $this->get(route('blog.show', $post))->assertOk()->assertSee('It works while you sleep');
});

test('an article needs a title, text and a unique web address', function () {
    Post::factory()->create(['slug' => 'taken']);

    Livewire::test('pages::admin.posts.form')
        ->call('save')
        ->assertHasErrors(['title' => 'required', 'body' => 'required']);

    Livewire::test('pages::admin.posts.form')
        ->set('title', 'Another')
        ->set('slug', 'taken')
        ->set('body', 'Text')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

test('the preview shows the formatted article', function () {
    Livewire::test('pages::admin.posts.form')
        ->set('body', '## A heading')
        ->set('showPreview', true)
        ->assertSeeHtml('<h2>A heading</h2>');
});

test('the admin can remove an article cover, unpublish and delete', function () {
    Storage::disk('public')->put('posts/cover.webp', 'x');
    Storage::disk('public')->put('posts/cover-sm.webp', 'x');
    $post = Post::factory()->create(['cover_path' => 'posts/cover.webp']);

    Livewire::test('pages::admin.posts.form', ['post' => $post])
        ->call('removeCoverImage')
        ->call('save')
        ->assertHasNoErrors();

    expect($post->refresh()->cover_path)->toBeNull();
    Storage::disk('public')->assertMissing(['posts/cover.webp', 'posts/cover-sm.webp']);

    Livewire::test('pages::admin.posts.index')->call('togglePublished', $post->id);
    expect($post->refresh()->is_published)->toBeFalse();

    Livewire::test('pages::admin.posts.index')->call('delete', $post->id);
    expect(Post::query()->count())->toBe(0);
});

test('the starter article is seeded as a draft', function () {
    $this->seed(PostSeeder::class);

    expect(Post::query()->sole())
        ->title->toBe('How much does a website cost in Nigeria?')
        ->is_published->toBeFalse();
});

test('users who are not admins cannot manage the blog', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.posts.index'))->assertForbidden();
});
