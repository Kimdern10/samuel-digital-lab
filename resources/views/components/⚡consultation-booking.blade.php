<?php

use App\Mail\ConsultationBooked;
use App\Mail\ConsultationConfirmed;
use App\Models\Consultation;
use App\Models\Setting;
use App\Support\ConsultationSlots;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $date = '';

    public string $time = '';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $method = 'whatsapp';

    public string $topic = '';

    /**
     * Hidden from people; spam bots fill it in.
     */
    public string $website = '';

    public ?int $bookedId = null;

    /**
     * Free times grouped by day.
     *
     * @return array<string, list<string>>
     */
    #[Computed]
    public function days(): array
    {
        return ConsultationSlots::available();
    }

    /**
     * The confirmed booking, once made.
     */
    #[Computed]
    public function booking(): ?Consultation
    {
        return $this->bookedId ? Consultation::query()->find($this->bookedId) : null;
    }

    /**
     * Choose a day (and clear the time picked for another day).
     */
    public function pickDate(string $date): void
    {
        $this->date = array_key_exists($date, $this->days) ? $date : '';
        $this->time = '';
    }

    /**
     * Book the call.
     */
    public function book(): void
    {
        $limiterKey = 'consultation:'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            $this->addError('time', 'You have booked a few calls already. Please message us on WhatsApp instead.');

            return;
        }

        $this->phone = trim($this->phone);
        $this->email = trim($this->email);

        $this->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-()]{6,18}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'method' => ['required', Rule::in(array_keys(Consultation::METHODS))],
            'topic' => ['nullable', 'string', 'max:1000'],
        ], [
            'date.required' => 'Pick a day.',
            'time.required' => 'Pick a time.',
            'name.required' => 'Please tell us your name.',
            'phone.required' => 'Add a phone or WhatsApp number so we can call you.',
            'phone.regex' => 'Please enter a valid phone number, e.g. 08012345678.',
        ]);

        RateLimiter::hit($limiterKey, 3600);

        if (filled($this->website)) {
            $this->reset('date', 'time');

            return;
        }

        $consultation = DB::transaction(function (): ?Consultation {
            // Lock the bookings so two visitors can't take the same time at once.
            Consultation::query()->booked()->lockForUpdate()->get(['id']);

            if (! ConsultationSlots::isAvailable($this->date, $this->time)) {
                return null;
            }

            return Consultation::query()->create([
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email ?: null,
                'method' => $this->method,
                'topic' => trim($this->topic) ?: null,
                'starts_at' => ConsultationSlots::toUtc($this->date, $this->time),
                'minutes' => ConsultationSlots::minutes(),
                'ip_address' => request()->ip(),
            ]);
        });

        if (! $consultation) {
            unset($this->days);
            $this->time = '';
            $this->addError('time', 'Sorry, someone just booked that time. Please pick another.');

            return;
        }

        try {
            Mail::to(Setting::get('email'))->send(new ConsultationBooked($consultation));

            if ($consultation->email) {
                Mail::to($consultation->email)->send(new ConsultationConfirmed($consultation));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->bookedId = $consultation->id;
    }
}; ?>

<div class="booking">
    @if ($this->booking)
        <div class="sent booking__done" role="status">
            <span class="sent__icon"><x-site-icon name="check" /></span>
            <strong>Your call is booked!</strong>
            <span><b>{{ $this->booking->when() }}</b> · {{ $this->booking->minutes }}-minute {{ strtolower($this->booking->methodLabel()) }}.</span>
            <span>We'll contact you on {{ $this->booking->phone }} at that time.@if ($this->booking->email) A confirmation has been sent to {{ $this->booking->email }}.@endif</span>
            <a class="btn btn--outline btn--sm" href="{{ $this->booking->googleCalendarUrl('Call with '.\App\Models\Setting::get('business_name')) }}" target="_blank" rel="noopener">Add to Google Calendar</a>
        </div>
    @elseif ($this->days === [])
        <p class="empty">Online booking is closed right now. Please <a class="link" href="{{ \App\Support\WhatsApp::link(\App\Models\Setting::get('whatsapp_primary'), 'Hello, I would like to book a call about a website.') }}" target="_blank" rel="noopener">message us on WhatsApp</a> and we'll find a time.</p>
    @else
        <form wire:submit="book" class="quote-form booking__form" novalidate>
            <h3>1. Pick a day</h3>
            <div class="booking__days" role="list">
                @foreach ($this->days as $day => $slots)
                    @php($moment = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $day, \App\Support\ConsultationSlots::TIMEZONE))
                    <button type="button" role="listitem" wire:key="day-{{ $day }}" wire:click="pickDate('{{ $day }}')" @class(['booking__day', 'is-active' => $date === $day]) aria-pressed="{{ $date === $day ? 'true' : 'false' }}">
                        <span>{{ $moment->isToday() ? 'Today' : ($moment->isTomorrow() ? 'Tomorrow' : $moment->format('D')) }}</span>
                        <b>{{ $moment->format('j') }}</b>
                        <span>{{ $moment->format('M') }}</span>
                    </button>
                @endforeach
            </div>
            @error('date') <span class="field-error">{{ $message }}</span> @enderror

            @if ($date !== '' && isset($this->days[$date]))
                <h3>2. Pick a time <small>(Nigerian time)</small></h3>
                <div class="booking__times">
                    @foreach ($this->days[$date] as $slot)
                        <button type="button" wire:key="time-{{ $date }}-{{ $slot }}" wire:click="$set('time', '{{ $slot }}')" @class(['booking__time', 'is-active' => $time === $slot]) aria-pressed="{{ $time === $slot ? 'true' : 'false' }}">{{ \Carbon\CarbonImmutable::createFromFormat('H:i', $slot)->format('g:ia') }}</button>
                    @endforeach
                </div>
            @endif
            @error('time') <span class="field-error" role="alert">{{ $message }}</span> @enderror

            @if ($time !== '')
                <h3>3. Your details</h3>
                <div class="row2">
                    <label for="c-name">Your name
                        <input id="c-name" wire:model="name" autocomplete="name" required @error('name') aria-invalid="true" @enderror>
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label for="c-phone">Phone / WhatsApp
                        <input id="c-phone" type="tel" wire:model="phone" autocomplete="tel" inputmode="tel" placeholder="080..." required @error('phone') aria-invalid="true" @enderror>
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <div class="row2">
                    <label for="c-email"><span>Email <small>(for a confirmation)</small></span>
                        <input id="c-email" type="email" wire:model="email" autocomplete="email" @error('email') aria-invalid="true" @enderror>
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <label for="c-method">How should we talk?
                        <select id="c-method" wire:model="method">
                            @foreach (\App\Models\Consultation::METHODS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label for="c-topic"><span>What would you like to talk about? <small>(optional)</small></span>
                    <textarea id="c-topic" wire:model="topic" rows="3" placeholder="e.g. I need a website for my restaurant with online orders."></textarea>
                </label>
                <div class="hp" aria-hidden="true"><input wire:model="website" tabindex="-1" autocomplete="off"></div>
                <button class="btn btn--primary btn--block" type="submit" wire:loading.attr="disabled" wire:target="book">
                    <span wire:loading.remove wire:target="book">Book my free call</span>
                    <span wire:loading wire:target="book">Booking…</span>
                </button>
                <p class="form-note">Free, no obligation. See our <a href="{{ route('privacy') }}">privacy policy</a>.</p>
            @endif
        </form>
    @endif
</div>
