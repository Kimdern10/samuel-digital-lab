<?php

use App\Models\User;

test('guests are sent to the login page from the admin', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('users who are not admins cannot open the admin', function (string $routeName) {
    $this->actingAs(User::factory()->create());

    $this->get(route($routeName))->assertForbidden();
})->with([
    'dashboard',
    'admin.works.index',
    'admin.works.create',
    'admin.quotes.index',
    'admin.services',
    'admin.settings',
    'admin.content',
    'admin.testimonials',
    'admin.faqs',
]);

test('the admin can open every admin page', function (string $routeName) {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route($routeName))->assertOk();
})->with([
    'dashboard',
    'admin.works.index',
    'admin.works.create',
    'admin.quotes.index',
    'admin.services',
    'admin.settings',
    'admin.content',
    'admin.testimonials',
    'admin.faqs',
]);

test('the admin lands on the dashboard after logging in', function () {
    $admin = User::factory()->admin()->create();

    $this->followingRedirects()
        ->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertOk()
        ->assertSee('Welcome back, '.$admin->name);
});

test('the website shows a dashboard link to the logged-in admin only', function () {
    $this->get(route('home'))->assertDontSee('data-test="admin-dashboard-link"', escape: false);

    $this->actingAs(User::factory()->create());
    $this->get(route('home'))->assertDontSee('data-test="admin-dashboard-link"', escape: false);

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('home'))->assertSee('data-test="admin-dashboard-link"', escape: false);
});

test('public registration is turned off', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->count())->toBe(0);
});
