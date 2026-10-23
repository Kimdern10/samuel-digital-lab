<?php

use App\Models\Service;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the six services from the design are seeded', function () {
    $this->seed(ServiceSeeder::class);

    expect(Service::query()->ordered()->pluck('title')->all())->toBe([
        'Business & portfolio websites',
        'Blog & news websites',
        'E-commerce websites',
        'Landing pages',
        'Custom web development',
        'Ongoing support',
    ]);
});

test('the admin can change a price and it shows on the home page', function () {
    $service = Service::factory()->create(['title' => 'Landing pages', 'price' => 80000]);

    Livewire::test('pages::admin.services')
        ->call('edit', $service->id)
        ->set('price', '₦95,000')
        ->set('features', "Ready for ads\n\nLoads fast")
        ->call('save')
        ->assertHasNoErrors();

    expect($service->refresh())
        ->price->toBe(95000)
        ->features->toBe(['Ready for ads', 'Loads fast']);

    $this->get(route('home'))->assertSee('₦95,000');
});

test('an empty price shows as on quote', function () {
    Livewire::test('pages::admin.services')
        ->call('create')
        ->set('title', 'Custom web development')
        ->set('description', 'Portals and booking systems.')
        ->set('price', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Service::query()->sole()->formattedPrice())->toBe('On quote');
});

test('the price must be a number', function () {
    Livewire::test('pages::admin.services')
        ->call('create')
        ->set('title', 'Landing pages')
        ->set('description', 'One page.')
        ->set('price', 'cheap')
        ->call('save')
        ->assertHasErrors(['price' => 'integer']);
});

test('the admin can delete a service', function () {
    $service = Service::factory()->create();

    Livewire::test('pages::admin.services')->call('delete', $service->id);

    expect(Service::query()->count())->toBe(0);
});
