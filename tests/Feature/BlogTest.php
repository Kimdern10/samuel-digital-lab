<?php

use App\Models\Post;
use App\Models\User;

test('the blog lists published articles only', function () {
    Post::factory()->create(['title' => 'Five signs your website is too slow']);
    Post::factory()->draft()->create(['title' => 'Unfinished draft']);
    Post::factory()->create(['title' => 'Scheduled for later', 'published_at' => now()->addWeek()]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('Five signs your website is too slow')
        ->assertDontSee('Unfinished draft')
        ->assertDontSee('Scheduled for later');
});

test('an article shows its formatted text with search engine markup', function () {
    $post = Post::factory()->create([
        'title' => 'How much does a website cost?',
        'body' => "## Our prices\n\nA **business website** starts at ₦150,000.\n\n- Fast\n- Mobile-first",
    ]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('<h2>Our prices</h2>', escape: false)
        ->assertSee('<strong>business website</strong>', escape: false)
        ->assertSee('"@type":"BlogPosting"', escape: false);
});

test('scripts and unsafe links written in an article are not run', function () {
    $post = Post::factory()->create(['body' => "Hello <script>alert('x')</script>\n\n[Click](javascript:alert(1))"]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertDontSee("<script>alert('x')</script>", escape: false)
        ->assertDontSee('href="javascript:alert(1)"', escape: false);
});

test('drafts and scheduled articles cannot be opened by visitors', function () {
    $this->get(route('blog.show', Post::factory()->draft()->create()))->assertNotFound();
    $this->get(route('blog.show', Post::factory()->create(['published_at' => now()->addDay()])))->assertNotFound();
});

test('the blog link and latest articles appear once an article is published', function () {
    $this->get(route('home'))->assertDontSee(route('blog.index').'"', escape: false);

    Post::factory()->create(['title' => 'Why your business needs a website']);

    $this->get(route('home'))
        ->assertSee(route('blog.index'), escape: false)
        ->assertSee('Why your business needs a website');
});

test('the sitemap lists published articles', function () {
    $post = Post::factory()->create();
    $draft = Post::factory()->draft()->create();

    $this->get(route('sitemap'))
        ->assertSee(route('blog.show', $post), escape: false)
        ->assertDontSee(route('blog.show', $draft), escape: false);
});

test('the blog can be filtered by topic', function () {
    Post::factory()->create(['title' => 'What a website costs', 'category' => 'Website prices']);
    Post::factory()->create(['title' => 'Rank higher on Google', 'category' => 'Google and SEO']);

    $this->get(route('blog.index'))
        ->assertSee('What a website costs')
        ->assertSee('Rank higher on Google')
        ->assertSee(route('blog.index', ['category' => 'Website prices']), escape: false);

    $this->get(route('blog.index', ['category' => 'Website prices']))
        ->assertSee('What a website costs')
        ->assertDontSee('Rank higher on Google');
});

test('articles can be searched by title, summary or text', function () {
    Post::factory()->create(['title' => 'Paystack for small shops', 'body' => 'Take card payments.']);
    Post::factory()->create(['title' => 'Speed matters', 'body' => 'Pages that open in a second keep visitors.']);

    $this->get(route('blog.index', ['q' => 'paystack']))
        ->assertSee('Paystack for small shops')
        ->assertDontSee('Speed matters')
        ->assertSee('1 article');

    $this->get(route('blog.index', ['q' => 'in a second']))->assertSee('Speed matters');

    $this->get(route('blog.index', ['q' => 'nothing matches this']))
        ->assertSee('No articles')
        ->assertSee('Try another word or topic.');
});

test('search results update live as the visitor types and picks a topic', function () {
    Post::factory()->create(['title' => 'Paystack for small shops', 'category' => 'Payments']);
    Post::factory()->create(['title' => 'Paystack or Flutterwave', 'category' => 'Website prices']);
    Post::factory()->create(['title' => 'Speed matters', 'category' => 'Website prices']);

    Livewire\Livewire::test('blog-search')
        ->assertSee('Speed matters')
        ->set('q', 'paystack')
        ->assertSee('2 articles')
        ->assertSee('Paystack for small shops')
        ->assertDontSee('Speed matters')
        ->call('filter', 'Website prices')
        ->assertSet('category', 'Website prices')
        ->assertSee('Paystack or Flutterwave')
        ->assertDontSee('Paystack for small shops')
        ->call('filter', 'Not a topic')
        ->assertSet('category', '')
        ->call('clear')
        ->assertSet('q', '')
        ->assertSee('Speed matters');
});

test('a search containing wildcard characters is treated as plain text', function () {
    Post::factory()->create(['title' => 'Prices in 2026']);

    $this->get(route('blog.index', ['q' => '%']))->assertDontSee('Prices in 2026');
});

test('the admin can give an article a topic', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test('pages::admin.posts.form')
        ->set('title', 'Why speed matters')
        ->set('body', 'Text')
        ->set('category', 'Google and SEO')
        ->call('save')
        ->assertHasNoErrors();

    expect(Post::query()->sole()->category)->toBe('Google and SEO');
});
