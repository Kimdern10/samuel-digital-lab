<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates the owner login as a verified admin', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Samuel',
        '--email' => 'Owner@Example.com',
        '--password' => 'a-strong-password',
    ])->assertSuccessful();

    $user = User::query()->sole();

    expect($user)
        ->name->toBe('Samuel')
        ->email->toBe('owner@example.com')
        ->is_admin->toBeTrue()
        ->email_verified_at->not->toBeNull()
        ->and(Hash::check('a-strong-password', $user->password))->toBeTrue();
});

test('it makes an existing user an admin and changes their password', function () {
    $user = User::factory()->create(['email' => 'owner@example.com', 'name' => 'Samuel']);

    $this->artisan('app:create-admin', [
        '--email' => 'owner@example.com',
        '--password' => 'another-password',
    ])->assertSuccessful();

    $user->refresh();

    expect(User::query()->count())->toBe(1)
        ->and($user->is_admin)->toBeTrue()
        ->and($user->name)->toBe('Samuel')
        ->and(Hash::check('another-password', $user->password))->toBeTrue();
});

test('it refuses an invalid email', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Samuel',
        '--email' => 'not-an-email',
        '--password' => 'a-strong-password',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
