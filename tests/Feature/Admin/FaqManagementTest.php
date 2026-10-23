<?php

use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('visible questions show on the home page with search engine markup', function () {
    Faq::factory()->create(['question' => 'How do I pay?', 'answer' => 'Half to start, half on launch day.']);
    Faq::factory()->hidden()->create(['question' => 'Secret question?']);

    $this->get(route('home'))
        ->assertSee('How do I pay?')
        ->assertSee('Half to start, half on launch day.')
        ->assertSee('"@type":"FAQPage"', escape: false)
        ->assertDontSee('Secret question?');
});

test('the admin can add, edit and delete a question', function () {
    Livewire::test('pages::admin.faqs')
        ->call('create')
        ->set('question', 'Do you build online shops?')
        ->set('answer', 'Yes, with Paystack or Flutterwave payments.')
        ->call('save')
        ->assertHasNoErrors();

    $faq = Faq::query()->sole();

    Livewire::test('pages::admin.faqs')
        ->call('edit', $faq->id)
        ->set('answer', 'Yes, paid in naira.')
        ->call('save')
        ->assertHasNoErrors();

    expect($faq->refresh()->answer)->toBe('Yes, paid in naira.');

    Livewire::test('pages::admin.faqs')->call('delete', $faq->id);

    expect(Faq::query()->count())->toBe(0);
});

test('a question needs words and an answer', function () {
    Livewire::test('pages::admin.faqs')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['question' => 'required', 'answer' => 'required']);
});
