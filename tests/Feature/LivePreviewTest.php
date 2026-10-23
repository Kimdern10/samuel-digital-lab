<?php

use App\Models\User;
use App\Models\Work;
use App\Support\LivePreview;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('a website can be shown live unless it blocks framing', function (array $headers, bool $expected) {
    Http::fake(['ezecatering.com.ng*' => Http::response('<html></html>', 200, $headers)]);

    expect(LivePreview::canEmbed('https://ezecatering.com.ng'))->toBe($expected);
})->with([
    'no blocking headers' => [[], true],
    'X-Frame-Options' => [['X-Frame-Options' => 'SAMEORIGIN'], false],
    'frame-ancestors self' => [['Content-Security-Policy' => "default-src 'self'; frame-ancestors 'self'"], false],
    'frame-ancestors anyone' => [['Content-Security-Policy' => 'frame-ancestors *'], true],
]);

test('plain http and unreachable websites are not shown live', function () {
    Http::fake(['down.example*' => Http::response('', 500)]);

    expect(LivePreview::canEmbed('http://ezecatering.com.ng'))->toBeFalse()
        ->and(LivePreview::canEmbed('https://down.example'))->toBeFalse();

    Http::assertSentCount(1);
});

test('the admin can type just the domain and the live preview is checked on save', function () {
    Http::fake(['ezecatering.com.ng*' => Http::response('<html></html>')]);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.works.form')
        ->set('title', 'Eze Catering')
        ->set('live_url', 'ezecatering.com.ng')
        ->call('save')
        ->assertHasNoErrors();

    expect(Work::query()->sole())
        ->live_url->toBe('https://ezecatering.com.ng')
        ->allows_embed->toBeTrue();
});

test('a project page shows the real website when it allows it', function () {
    $work = Work::factory()->create(['live_url' => 'https://ezecatering.com.ng', 'allows_embed' => true]);

    $this->get(route('work.show', $work))
        ->assertOk()
        ->assertSee('<iframe class="live__frame" src="https://ezecatering.com.ng"', escape: false);
});

test('a project page shows a screenshot when the website blocks framing', function () {
    $work = Work::factory()->create(['live_url' => 'https://ezecatering.com.ng', 'allows_embed' => false]);

    $this->get(route('work.show', $work))
        ->assertOk()
        ->assertDontSee('<iframe', escape: false)
        ->assertSee(LivePreview::screenshotUrl('https://ezecatering.com.ng', 1600, 1000));
});

test('works without a cover use a screenshot of their website on cards', function () {
    Work::factory()->create(['live_url' => 'https://ezecatering.com.ng', 'cover_path' => null]);

    $this->get(route('work.index'))
        ->assertSee(LivePreview::screenshotUrl('https://ezecatering.com.ng', 800, 500));
});
