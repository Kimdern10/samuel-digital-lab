<?php

use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\CostEstimator;
use Livewire\Livewire;

test('the estimate adds the base price, extra pages and extras', function () {
    // Business website: ₦150,000 with 6 pages; 2 extra pages at ₦10,000; payments ₦40,000 + logo ₦25,000.
    $estimate = CostEstimator::estimate(1, 8, [0, 1]);

    expect($estimate['type'])->toBe('Business & portfolio websites')
        ->and($estimate['extra_pages'])->toBe(2)
        ->and($estimate['total'])->toBe(150000 + 20000 + 40000 + 25000);
});

test('fewer pages than included, repeated or unknown extras do not change the price', function () {
    expect(CostEstimator::estimate(1, 2, [0, 0, 99])['total'])->toBe(150000 + 40000)
        ->and(CostEstimator::estimate(99, 5, [])['total'])->toBe(0);
});

test('the estimate fills the quote form with the service, budget and details', function () {
    Service::factory()->create(['title' => 'E-commerce websites']);

    Livewire::test('cost-calculator')
        ->set('type', 3)
        ->set('pages', 10)
        ->set('extras', ['0'])
        ->call('useEstimate')
        ->assertDispatched('estimate-ready', service: 'E-commerce websites', budget: '₦200k – ₦400k')
        ->assertHasNoErrors();

    Livewire::test('quote-form')
        ->dispatch('estimate-ready', service: 'E-commerce websites', budget: '₦200k – ₦400k', details: 'Estimated total: ₦390,000')
        ->assertSet('service', 'E-commerce websites')
        ->assertSet('budget', '₦200k – ₦400k')
        ->assertSet('details', 'Estimated total: ₦390,000');
});

test('an estimate for a type that is not a service is sent as something else', function () {
    Livewire::test('quote-form')
        ->dispatch('estimate-ready', service: 'Mobile app', budget: 'made up', details: 'x')
        ->assertSet('service', 'Something else')
        ->assertSet('budget', '');
});

test('the home page shows the calculator with the admin prices', function () {
    $this->get(route('home'))
        ->assertSee('What will your website cost?')
        ->assertSeeLivewire('cost-calculator')
        ->assertSee('Online payments (Paystack or Flutterwave)');
});

test('the admin can change the calculator prices', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.calculator')
        ->set('types.0.price', '₦95,000')
        ->set('texts.calc_extra_page_price', '12,000')
        ->call('removeRow', 'extras', 0)
        ->call('addRow', 'extras')
        ->set('extras.5.name', 'Live chat')
        ->set('extras.5.price', '8000')
        ->call('save')
        ->assertHasNoErrors();

    expect(CostEstimator::siteTypes()[0]['price'])->toBe(95000)
        ->and(CostEstimator::extraPagePrice())->toBe(12000)
        ->and(collect(CostEstimator::extras())->pluck('name'))->not->toContain('Online payments (Paystack or Flutterwave)')->toContain('Live chat');
});

test('calculator prices must be numbers and every row needs a name', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.calculator')
        ->set('types.0.price', 'cheap')
        ->set('extras.0.name', '')
        ->call('save')
        ->assertHasErrors(['types.0.price', 'extras.0.name']);

    expect(Setting::query()->where('key', 'calc_site_types')->exists())->toBeFalse();
});
