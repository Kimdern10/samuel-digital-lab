<?php

use App\Models\Consultation;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Booked calls')] class extends Component {
    /** @var array<string, string> */
    public array $hours = [];

    /** @var list<string> */
    public array $days = [];

    public bool $enabled = true;

    public bool $showPast = false;

    /**
     * Load the booking hours.
     */
    public function mount(): void
    {
        $this->hours = array_intersect_key(Setting::values(), array_flip(['consult_start', 'consult_end', 'consult_minutes', 'consult_interval', 'consult_days_ahead', 'consult_notice_hours']));
        $this->days = array_map('strval', Setting::list('consult_days'));
        $this->enabled = Setting::get('consult_enabled') === '1';
    }

    /**
     * Calls to show: upcoming by default, or past ones.
     *
     * @return Collection<int, Consultation>
     */
    #[Computed]
    public function calls(): Collection
    {
        return Consultation::query()
            ->when(! $this->showPast, fn ($query) => $query->where('starts_at', '>=', now()->subHour())->orderBy('starts_at'))
            ->when($this->showPast, fn ($query) => $query->where('starts_at', '<', now()->subHour())->orderByDesc('starts_at'))
            ->limit(100)
            ->get();
    }

    /**
     * Mark a call as done, cancelled or booked again.
     */
    public function setStatus(int $id, string $status): void
    {
        abort_unless(array_key_exists($status, Consultation::STATUSES), 404);

        Consultation::query()->findOrFail($id)->update(['status' => $status]);
        unset($this->calls);

        Flux::toast(variant: 'success', text: __('Call marked as :status.', ['status' => strtolower(Consultation::STATUSES[$status])]));
    }

    /**
     * Save the booking hours.
     */
    public function saveHours(): void
    {
        $this->validate([
            'enabled' => ['boolean'],
            'days' => ['array'],
            'days.*' => [Rule::in(['1', '2', '3', '4', '5', '6', '7'])],
            'hours.consult_start' => ['required', 'date_format:H:i'],
            'hours.consult_end' => ['required', 'date_format:H:i', 'after:hours.consult_start'],
            'hours.consult_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'hours.consult_interval' => ['required', 'integer', 'min:5', 'max:240'],
            'hours.consult_days_ahead' => ['required', 'integer', 'min:1', 'max:60'],
            'hours.consult_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
        ], [
            'hours.consult_end.after' => 'The end time must be after the start time.',
        ]);

        Setting::put([
            ...$this->hours,
            'consult_enabled' => $this->enabled ? '1' : '0',
            'consult_days' => array_map('intval', array_values(array_unique($this->days))),
        ]);

        Flux::toast(variant: 'success', text: __('Booking hours saved.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Booked calls') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Free consultation calls booked on your website. Times are in Nigerian time (WAT).') }}</flux:text>
        </div>
        <flux:button icon="arrow-top-right-on-square" :href="route('consultation')" target="_blank">{{ __('View booking page') }}</flux:button>
    </div>

    <div class="flex gap-2">
        <flux:button size="sm" :variant="$showPast ? 'outline' : 'primary'" wire:click="$set('showPast', false)">{{ __('Upcoming') }}</flux:button>
        <flux:button size="sm" :variant="$showPast ? 'primary' : 'outline'" wire:click="$set('showPast', true)">{{ __('Past') }}</flux:button>
    </div>

    @if ($this->calls->isEmpty())
        <flux:callout icon="phone">{{ $showPast ? __('No past calls.') : __('No upcoming calls yet. Share your booking page link with people who ask about websites.') }}</flux:callout>
    @else
        <div class="grid gap-3">
            @foreach ($this->calls as $call)
                <div wire:key="call-{{ $call->id }}" class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 {{ $call->status === 'cancelled' ? 'opacity-60' : '' }}">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $call->when() }}</div>
                        <div class="text-sm text-zinc-500">{{ $call->name }} · {{ $call->phone }} · {{ $call->methodLabel() }}</div>
                        @if ($call->topic)
                            <flux:text class="mt-1 text-sm">{{ $call->topic }}</flux:text>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm" :color="match ($call->status) { 'done' => 'green', 'cancelled' => 'zinc', default => 'amber' }">{{ \App\Models\Consultation::STATUSES[$call->status] ?? $call->status }}</flux:badge>
                        <flux:button size="sm" icon="chat-bubble-left-right" :href="\App\Support\WhatsApp::link($call->phone, 'Hello '.$call->name.', this is about your call with '.\App\Models\Setting::get('business_name').'.')" target="_blank">{{ __('WhatsApp') }}</flux:button>
                        @if ($call->status === 'booked')
                            <flux:button size="sm" wire:click="setStatus({{ $call->id }}, 'done')">{{ __('Done') }}</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="setStatus({{ $call->id }}, 'cancelled')" wire:confirm="{{ __('Cancel this call? The time becomes free again.') }}">{{ __('Cancel') }}</flux:button>
                        @else
                            <flux:button size="sm" variant="ghost" wire:click="setStatus({{ $call->id }}, 'booked')">{{ __('Mark as booked') }}</flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:card class="grid gap-5">
        <div>
            <flux:heading size="lg">{{ __('Your booking hours') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Visitors can only pick free times inside these hours.') }}</flux:text>
        </div>
        <form wire:submit="saveHours" class="grid gap-5">
            <flux:switch wire:model="enabled" :label="__('Allow online booking')" />
            <div>
                <flux:text class="mb-2 font-medium">{{ __('Days') }}</flux:text>
                <div class="flex flex-wrap gap-4">
                    @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $value => $label)
                        <flux:checkbox wire:model="days" :value="(string) $value" :label="$label" wire:key="day-{{ $value }}" />
                    @endforeach
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="hours.consult_start" type="time" :label="__('From')" />
                <flux:input wire:model="hours.consult_end" type="time" :label="__('Until')" />
                <flux:input wire:model="hours.consult_minutes" type="number" min="5" :label="__('Call length (minutes)')" />
                <flux:input wire:model="hours.consult_interval" type="number" min="5" :label="__('A slot every (minutes)')" />
                <flux:input wire:model="hours.consult_days_ahead" type="number" min="1" :label="__('Days ahead to show')" />
                <flux:input wire:model="hours.consult_notice_hours" type="number" min="0" :label="__('Minimum notice (hours)')" />
            </div>
            <div><flux:button variant="primary" type="submit">{{ __('Save hours') }}</flux:button></div>
        </form>
    </flux:card>
</div>
