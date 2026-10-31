<?php

use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Work;

test('the home page shows visible services with their prices', function () {
    Service::factory()->create(['title' => 'Landing pages', 'price' => 80000, 'price_note' => 'from · 3 days']);
    Service::factory()->create(['title' => 'Custom web development', 'price' => null]);
    Service::factory()->hidden()->create(['title' => 'Secret service']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('We build online')
        ->assertSee('Landing pages')
        ->assertSee('₦80,000')
        ->assertSee('from · 3 days')
        ->assertSee('On quote')
        ->assertDontSee('Secret service');
});

test('the home page features published works only', function () {
    Work::factory()->featured()->create(['title' => 'Eze Catering website']);
    Work::factory()->featured()->draft()->create(['title' => 'Unfinished draft']);
    Work::factory()->create(['title' => 'Not featured']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Eze Catering website')
        ->assertDontSee('Unfinished draft')
        ->assertDontSee('Not featured');
});

test('the home page shows the business details from settings', function () {
    Setting::put(['whatsapp_primary' => '08011112222', 'city' => 'Enugu, Enugu State']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('08011112222')
        ->assertSee('https://wa.me/2348011112222', escape: false)
        ->assertSee('Enugu, Enugu State');
});

test('the work page lists published works only', function () {
    Work::factory()->create(['title' => 'Published shop']);
    Work::factory()->draft()->create(['title' => 'Draft shop']);

    $this->get(route('work.index'))
        ->assertOk()
        ->assertSee('Published shop')
        ->assertDontSee('Draft shop');
});

test('a published work has its own page', function () {
    $work = Work::factory()->create([
        'title' => 'Eze Catering website',
        'client' => 'Eze Catering Services',
        'technologies' => ['Laravel', 'MySQL'],
        'results' => [['value' => '2 → 366', 'label' => 'pages Google can find']],
        'live_url' => 'https://ezecatering.example.ng',
        'screenshots' => ['works/one.webp', 'works/two.webp'],
    ]);

    $this->get(route('work.show', $work))
        ->assertOk()
        ->assertSee('Eze Catering website')
        ->assertSee('Eze Catering Services')
        ->assertSee('MySQL')
        ->assertSee('2 → 366')
        ->assertSee('https://ezecatering.example.ng')
        ->assertSee('/storage/works/one-sm.webp')
        ->assertSee('data-lightbox-src', escape: false);
});

test('each work shows the domain of its website', function () {
    $work = Work::factory()->create(['live_url' => 'https://www.ezecatering.com.ng']);

    $this->get(route('work.index'))->assertSee('ezecatering.com.ng');
    $this->get(route('work.show', $work))->assertSee('ezecatering.com.ng');
});

test('a missing page shows the branded not found page', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('This page is')
        ->assertSee('Go to the home page');
});

test('the work page can be filtered by type of website', function () {
    Work::factory()->create(['title' => 'Eze Shop', 'category' => 'E-commerce website']);
    Work::factory()->create(['title' => 'Daily News', 'category' => 'Blog & news website']);

    $this->get(route('work.index'))
        ->assertSee('Eze Shop')
        ->assertSee('Daily News')
        ->assertSee(route('work.index', ['category' => 'E-commerce website']), escape: false);

    $this->get(route('work.index', ['category' => 'E-commerce website']))
        ->assertSee('Eze Shop')
        ->assertDontSee('Daily News');

    $this->get(route('work.index', ['category' => 'Not a real category']))
        ->assertSee('Eze Shop')
        ->assertSee('Daily News');
});

test('the Google reviews card shows once a rating and link are saved', function () {
    $this->get(route('home'))->assertDontSee('data-test="google-rating"', escape: false);

    $this->actingAs(User::factory()->admin()->create());
    Livewire\Livewire::test('pages::admin.settings')
        ->set('reviews.google_rating', '4.9')
        ->set('reviews.google_review_count', '23')
        ->set('reviews.google_reviews_url', 'https://g.page/r/samueldigitallab/review')
        ->call('saveReviews')
        ->assertHasNoErrors();

    $this->get(route('home'))
        ->assertSee('data-test="google-rating"', escape: false)
        ->assertSee('Based on 23 Google reviews')
        ->assertSee('https://g.page/r/samueldigitallab/review', escape: false);
});

test('the Google rating must be between 1 and 5 and needs a link', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test('pages::admin.settings')
        ->set('reviews.google_rating', '7')
        ->call('saveReviews')
        ->assertHasErrors(['reviews.google_rating', 'reviews.google_reviews_url']);
});

test('a draft work cannot be opened by visitors', function () {
    $work = Work::factory()->draft()->create();

    $this->get(route('work.show', $work))->assertNotFound();
});

test('the sitemap lists published works and robots.txt points to it', function () {
    $published = Work::factory()->create(['slug' => 'eze-catering']);
    $draft = Work::factory()->draft()->create(['slug' => 'hidden-draft']);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('work.show', $published), escape: false)
        ->assertDontSee('hidden-draft');

    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'))
        ->assertSee('Disallow: /admin');
});
