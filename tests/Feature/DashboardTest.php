<?php

use App\Models\QuoteRequest;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the admin can visit the dashboard and see new quote requests', function () {
    QuoteRequest::factory()->create(['name' => 'Ngozi Eze']);
    $this->actingAs(User::factory()->admin()->create());

    $response = $this->get(route('dashboard'));
    $response->assertOk()->assertSee('Ngozi Eze');
});
