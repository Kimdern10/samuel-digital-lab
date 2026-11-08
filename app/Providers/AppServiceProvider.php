<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthEmails();
    }

    /**
     * Word the email-confirmation and password-reset emails in the site's own voice.
     * They use the branded email layout (logo header and footer) like every other email.
     */
    protected function configureAuthEmails(): void
    {
        VerifyEmail::toMailUsing(fn (User $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject('Confirm your email for '.Setting::get('business_name'))
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Please confirm this is your email address so we can keep your account safe and send you updates about your website.')
            ->action('Confirm my email', $url)
            ->line('This link works for '.config('auth.verification.expire', 60).' minutes. If you did not expect this email, you can ignore it.')
            ->salutation('Thank you, '.Setting::get('business_name')));

        ResetPassword::toMailUsing(function (User $notifiable, string $token): MailMessage {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
            $hours = intdiv((int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'), 60);

            return (new MailMessage)
                ->subject('Reset your '.Setting::get('business_name').' password')
                ->greeting('Hi '.$notifiable->name.',')
                ->line('We received a request to reset the password for your account.')
                ->action('Choose a new password', $url)
                ->line('This link works for '.trans_choice('{1} 1 hour|[2,*] :count hours', max(1, $hours)).'.')
                ->line('If you did not ask for this, you can ignore this email. Your password will stay the same.')
                ->salutation('Thank you, '.Setting::get('business_name'));
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
