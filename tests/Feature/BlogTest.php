<?php

use App\Models\Post;

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
