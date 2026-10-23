<?php

use App\Mail\QuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /**
     * How many requests one visitor may send in ten minutes.
     */
    public const int MAX_ATTEMPTS = 3;

    public string $name = '';

    public string $business = '';

    public string $phone = '';

    public string $email = '';

    public string $service = '';

    public string $budget = '';

    public string $details = '';

    /**
     * Hidden from people; spam bots fill it in.
     */
    public string $website = '';

    public bool $sent = false;

    /**
     * The services a visitor can ask about.
     *
     * @return list<string>
     */
    #[Computed]
    public function serviceOptions(): array
    {
        return [...Service::query()->visible()->ordered()->pluck('title')->all(), 'Something else'];
    }

    /**
     * Get the validation rules for the form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'business' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-()]{6,18}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'service' => ['required', Rule::in($this->serviceOptions)],
            'budget' => ['required', Rule::in(QuoteRequest::BUDGETS)],
            'details' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Please tell us your name.',
            'phone.required' => 'Please add a phone or WhatsApp number so we can reply.',
            'phone.regex' => 'Please enter a valid phone number, e.g. 08012345678.',
            'service.required' => 'Please choose what you need.',
            'service.in' => 'Please choose what you need.',
            'budget.required' => 'Please choose a budget.',
            'budget.in' => 'Please choose a budget.',
            'details.required' => 'Please tell us a little about the website.',
            'details.min' => 'Please tell us a little more (at least 10 characters).',
        ];
    }

    /**
     * Save the request and let the owner know.
     */
    public function submit(): void
    {
        $limiterKey = 'quote-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);
            $this->addError('form', "You've sent a few requests already. Please try again in {$minutes} minutes, or message us on WhatsApp.");

            return;
        }

        $validated = $this->validate();

        RateLimiter::hit($limiterKey, 600);

        if (filled($this->website)) {
            $this->finish();

            return;
        }

        $quote = QuoteRequest::query()->create([
            ...$validated,
            'business' => $validated['business'] ?: null,
            'email' => $validated['email'] ?: null,
            'ip_address' => request()->ip(),
        ]);

        try {
            Mail::to(Setting::get('email'))->send(new QuoteRequestReceived($quote));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->finish();
    }

    /**
     * Clear the form and show the thank-you message.
     */
    private function finish(): void
    {
        $this->reset('name', 'business', 'phone', 'email', 'service', 'budget', 'details', 'website');
        $this->sent = true;
    }
}; ?>

<form id="quote" class="quote-form" wire:submit="submit" novalidate>
    <h3>{{ \App\Models\Setting::get('form_title') }}</h3>

    @if ($sent)
        <div class="sent" role="status">
            <span class="sent__icon"><x-site-icon name="check" /></span>
            <strong>Thank you! Your request has been sent.</strong>
            <span>We'll reply on WhatsApp or by email within one working day.</span>
            <button class="btn btn--outline btn--sm" type="button" wire:click="$set('sent', false)">Send another request</button>
        </div>
    @else
        <div class="row2">
            <label for="q-name">Your name
                <input id="q-name" wire:model="name" autocomplete="name" required @error('name') aria-invalid="true" aria-describedby="q-name-error" @enderror>
                @error('name') <span class="field-error" id="q-name-error">{{ $message }}</span> @enderror
            </label>
            <label for="q-business"><span>Business <small>(optional)</small></span>
                <input id="q-business" wire:model="business" autocomplete="organization">
                @error('business') <span class="field-error">{{ $message }}</span> @enderror
            </label>
        </div>
        <div class="row2">
            <label for="q-phone">Phone / WhatsApp
                <input id="q-phone" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" placeholder="080..." required @error('phone') aria-invalid="true" aria-describedby="q-phone-error" @enderror>
                @error('phone') <span class="field-error" id="q-phone-error">{{ $message }}</span> @enderror
            </label>
            <label for="q-email"><span>Email <small>(optional)</small></span>
                <input id="q-email" type="email" wire:model="email" autocomplete="email" @error('email') aria-invalid="true" aria-describedby="q-email-error" @enderror>
                @error('email') <span class="field-error" id="q-email-error">{{ $message }}</span> @enderror
            </label>
        </div>
        <div class="row2">
            <label for="q-type">What you need
                <select id="q-type" wire:model="service" required @error('service') aria-invalid="true" aria-describedby="q-type-error" @enderror>
                    <option value="">Choose one</option>
                    @foreach ($this->serviceOptions as $option)
                        <option value="{{ $option }}" wire:key="service-{{ $loop->index }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('service') <span class="field-error" id="q-type-error">{{ $message }}</span> @enderror
            </label>
            <label for="q-budget">Budget
                <select id="q-budget" wire:model="budget" required @error('budget') aria-invalid="true" aria-describedby="q-budget-error" @enderror>
                    <option value="">Choose one</option>
                    @foreach (\App\Models\QuoteRequest::BUDGETS as $option)
                        <option value="{{ $option }}" wire:key="budget-{{ $loop->index }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('budget') <span class="field-error" id="q-budget-error">{{ $message }}</span> @enderror
            </label>
        </div>
        <label for="q-details">Tell us a little more
            <textarea id="q-details" wire:model="details" required placeholder="e.g. I need a site where customers can see our menu, book catering for events and pay a deposit online." @error('details') aria-invalid="true" aria-describedby="q-details-error" @enderror></textarea>
            @error('details') <span class="field-error" id="q-details-error">{{ $message }}</span> @enderror
        </label>

        <div class="hp" aria-hidden="true">
            <label for="q-website">Leave this empty<input id="q-website" wire:model="website" tabindex="-1" autocomplete="off"></label>
        </div>

        @error('form') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        <button class="btn btn--primary btn--block" type="submit" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">Send request</span>
            <span wire:loading wire:target="submit">Sending…</span>
            <x-site-icon name="arrow-right" wire:loading.remove wire:target="submit" />
        </button>
        @if (filled($formNote = \App\Models\Setting::get('form_note')))
            <p class="form-note">{{ $formNote }}</p>
        @endif
    @endif
</form>
