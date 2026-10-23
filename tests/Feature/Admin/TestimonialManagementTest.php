<?php

use App\Models\Testimonial;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the testimonials section is hidden until there is one', function () {
    $this->get(route('home'))->assertDontSee('What our clients say');

    Testimonial::factory()->hidden()->create();

    $this->get(route('home'))->assertDontSee('What our clients say');
});

test('the admin can add a testimonial that shows on the home page', function () {
    Livewire::test('pages::admin.testimonials')
        ->call('create')
        ->set('name', 'Ngozi Eze')
        ->set('role', 'Owner, Eze Catering Services')
        ->set('quote', 'Bookings now come in through the website every week.')
        ->set('rating', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect(Testimonial::query()->sole()->name)->toBe('Ngozi Eze');

    $this->get(route('home'))
        ->assertSee('What our clients say')
        ->assertSee('Bookings now come in through the website every week.')
        ->assertSee('Owner, Eze Catering Services');
});

test('the admin can edit and delete a testimonial', function () {
    $testimonial = Testimonial::factory()->create(['quote' => 'Old words.']);

    Livewire::test('pages::admin.testimonials')
        ->call('edit', $testimonial->id)
        ->assertSet('quote', 'Old words.')
        ->set('quote', 'New words.')
        ->call('save')
        ->assertHasNoErrors();

    expect($testimonial->refresh()->quote)->toBe('New words.')
        ->and(Testimonial::query()->count())->toBe(1);

    Livewire::test('pages::admin.testimonials')->call('delete', $testimonial->id);

    expect(Testimonial::query()->count())->toBe(0);
});

test('a testimonial needs a name, words and a rating from 1 to 5', function () {
    Livewire::test('pages::admin.testimonials')
        ->call('create')
        ->set('rating', 9)
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'quote' => 'required', 'rating' => 'max']);
});
