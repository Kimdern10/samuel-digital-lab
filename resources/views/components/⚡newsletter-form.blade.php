<?php

use App\Models\Subscriber;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

new class extends Component {
    public string $email = '';

    /**
     * Hidden from people; spam bots fill it in.
     */
    public string $website = '';

    public bool $subscribed = false;

    /**
     * Add the visitor to the newsletter list.
     */
    public function subscribe(): void
    {
        $limiterKey = 'newsletter:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->addError('email', 'Too many attempts. Please try again later.');

            return;
        }

        $this->email = trim($this->email);

        $this->validate(['email' => ['required', 'email', 'max:150']], [
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
        ]);

        RateLimiter::hit($limiterKey, 3600);

        if (blank($this->website)) {
            Subscriber::query()->updateOrCreate(
                ['email' => strtolower(trim($this->email))],
                ['unsubscribed_at' => null, 'ip_address' => request()->ip()],
            );
        }

        $this->reset('email', 'website');
        $this->subscribed = true;
    }
}; ?>

<div class="newsletter">
    <h4>Get website tips</h4>
    @if ($subscribed)
        <p class="newsletter__done" role="status">Thanks! You're on the list. We send a short email now and then, never spam.</p>
    @else
        <p>Short, useful tips on websites and getting customers online. No spam.</p>
        <form wire:submit="subscribe" class="newsletter__form" novalidate>
            <label class="sr-only" for="newsletter-email">Email address</label>
            <input id="newsletter-email" type="email" wire:model="email" placeholder="you@example.com" autocomplete="email" required @error('email') aria-invalid="true" @enderror>
            <div class="hp" aria-hidden="true"><input wire:model="website" tabindex="-1" autocomplete="off"></div>
            <button type="submit" class="btn btn--primary btn--sm" wire:loading.attr="disabled">Subscribe</button>
        </form>
        @error('email') <p class="newsletter__error">{{ $message }}</p> @enderror
    @endif
</div>
