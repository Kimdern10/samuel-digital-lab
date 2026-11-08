<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('the email confirmation and password reset emails carry the logo, business name and footer', function () {
    $user = User::factory()->create(['name' => 'Ngozi Eze']);

    $verify = (new VerifyEmail)->toMail($user);
    $reset = (new ResetPassword('token'))->toMail($user);

    expect($verify->subject)->toBe('Confirm your email for '.Setting::get('business_name'))
        ->and($reset->subject)->toBe('Reset your '.Setting::get('business_name').' password')
        ->and((string) $reset->render())->toContain('48 hours');

    foreach ([$verify, $reset] as $mail) {
        expect((string) $mail->render())
            ->toContain('Hi Ngozi Eze,')
            ->toContain('class="footer-name"')
            ->not->toContain('Laravel');
    }
});

test('while the site is only on this computer the logo is drawn, since email apps cannot load its pictures', function () {
    config(['app.url' => 'http://samuel-digital-lab.test']);

    expect((string) (new VerifyEmail)->toMail(User::factory()->create())->render())
        ->toContain('background-color: #0e8f57')
        ->not->toContain('logo-icon-512.png');
});

test('once the site is live the logo picture is used', function () {
    config(['app.url' => 'https://samueldigitallab.com']);
    URL::forceRootUrl('https://samueldigitallab.com');
    URL::forceScheme('https');

    expect((string) (new VerifyEmail)->toMail(User::factory()->create())->render())
        ->toContain('https://samueldigitallab.com/brand/logo-icon-512.png');
});

test('an uploaded logo gets a PNG copy that emails use instead of the WebP file', function () {
    config(['app.url' => 'https://samueldigitallab.com']);
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.settings')->set('logo', UploadedFile::fake()->image('logo.png', 900, 300));

    $pngPath = ImageOptimizer::emailLogoPath(Setting::get('logo_path'));
    Storage::disk('public')->assertExists($pngPath);

    expect((string) (new VerifyEmail)->toMail(User::factory()->create())->render())
        ->toContain(Storage::disk('public')->url($pngPath))
        ->not->toContain('.webp');

    Livewire::test('pages::admin.settings')->call('removeLogo');

    Storage::disk('public')->assertMissing($pngPath);
});
