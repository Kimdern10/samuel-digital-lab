<?php

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Support\WhatsApp;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quote request')] class extends Component {
    public QuoteRequest $quoteRequest;

    public string $status = '';

    public string $notes = '';

    public string $followUpOn = '';

    /**
     * Load the request.
     */
    public function mount(QuoteRequest $quoteRequest): void
    {
        $this->quoteRequest = $quoteRequest;
        $this->status = $quoteRequest->status->value;
        $this->notes = (string) $quoteRequest->notes;
        $this->followUpOn = (string) $quoteRequest->follow_up_on?->format('Y-m-d');
    }

    /**
     * Save the private notes and the follow-up date.
     */
    public function saveFollowUp(): void
    {
        $this->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
            'followUpOn' => ['nullable', 'date'],
        ]);

        $this->quoteRequest->update([
            'notes' => trim($this->notes) ?: null,
            'follow_up_on' => $this->followUpOn ?: null,
        ]);

        Flux::toast(variant: 'success', text: $this->followUpOn
            ? __('Saved. You will be reminded on :date.', ['date' => $this->quoteRequest->follow_up_on?->format('j M Y')])
            : __('Notes saved.'));
    }

    /**
     * Set the follow-up date a number of days from today.
     */
    public function remindIn(int $days): void
    {
        $this->followUpOn = now()->addDays(max(0, min($days, 365)))->format('Y-m-d');
        $this->saveFollowUp();
    }

    /**
     * Save the new status as soon as it is picked.
     */
    public function updatedStatus(): void
    {
        $this->validate(['status' => ['required', Rule::enum(QuoteStatus::class)]]);

        $this->quoteRequest->update(['status' => $this->status]);

        Flux::toast(variant: 'success', text: __('Status changed to :status.', ['status' => $this->quoteRequest->status->label()]));
    }

    /**
     * A WhatsApp link that opens a chat with a greeting already typed.
     */
    #[Computed]
    public function whatsAppUrl(): string
    {
        $business = Setting::get('business_name');
        $message = "Hello {$this->quoteRequest->name}, this is {$business}. Thank you for asking about {$this->quoteRequest->service} on our website. When is a good time for a quick chat about it?";

        return WhatsApp::link($this->quoteRequest->phone, $message);
    }

    /**
     * Delete the request.
     */
    public function delete(): void
    {
        $this->quoteRequest->delete();

        Flux::toast(variant: 'success', text: __('Quote request deleted.'));

        $this->redirectRoute('admin.quotes.index', navigate: true);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('admin.quotes.index')" wire:navigate class="text-sm">← {{ __('All quote requests') }}</flux:link>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <flux:heading size="xl" level="1">{{ $quoteRequest->name }}</flux:heading>
            <flux:badge :color="$quoteRequest->status->color()">{{ $quoteRequest->status->label() }}</flux:badge>
        </div>
        <flux:text class="mt-1">{{ __('Sent :date', ['date' => $quoteRequest->created_at->format('l j F Y, g:ia')]) }} ({{ $quoteRequest->created_at->diffForHumans() }})</flux:text>
    </div>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" icon="chat-bubble-left-right" :href="$this->whatsAppUrl" target="_blank" class="bg-[#25d366]! text-white! border-[#25d366]!" data-test="whatsapp-reply">{{ __('Reply on WhatsApp') }}</flux:button>
        <flux:button icon="phone" href="tel:{{ $quoteRequest->phone }}">{{ __('Call') }}</flux:button>
        @if ($quoteRequest->email)
            <flux:button icon="envelope" href="mailto:{{ $quoteRequest->email }}?subject={{ rawurlencode('Your website quote request') }}">{{ __('Email') }}</flux:button>
        @endif
    </div>

    <flux:card class="grid gap-4">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-zinc-500">{{ __('Business') }}</dt><dd class="font-medium">{{ $quoteRequest->business ?: '—' }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Phone / WhatsApp') }}</dt><dd class="font-mono font-medium">{{ $quoteRequest->phone }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Email') }}</dt><dd class="font-medium break-all">{{ $quoteRequest->email ?: '—' }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('What they need') }}</dt><dd class="font-medium">{{ $quoteRequest->service }}</dd></div>
            <div><dt class="text-sm text-zinc-500">{{ __('Budget') }}</dt><dd class="font-medium">{{ $quoteRequest->budget }}</dd></div>
        </dl>
        <flux:separator />
        <div>
            <div class="text-sm text-zinc-500">{{ __('Their message') }}</div>
            <p class="mt-1 whitespace-pre-line">{{ $quoteRequest->details }}</p>
        </div>
    </flux:card>

    <flux:card class="grid gap-4">
        <div>
            <flux:heading size="lg">{{ __('Your notes and follow-up') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Only you can see these. Set a date and this request appears on your dashboard on that day.') }}</flux:text>
        </div>
        <form wire:submit="saveFollowUp" class="grid gap-4">
            <flux:textarea wire:model="notes" :label="__('Private notes')" rows="4" :placeholder="__('e.g. Called on Monday, wants a quote for 5 pages plus online payments.')" />
            <div class="flex flex-wrap items-end gap-3">
                <flux:input wire:model="followUpOn" type="date" :label="__('Follow up on')" class="max-w-52" />
                <flux:button size="sm" wire:click="remindIn(1)">{{ __('Tomorrow') }}</flux:button>
                <flux:button size="sm" wire:click="remindIn(3)">{{ __('In 3 days') }}</flux:button>
                <flux:button size="sm" wire:click="remindIn(7)">{{ __('Next week') }}</flux:button>
            </div>
            <div><flux:button variant="primary" type="submit">{{ __('Save notes') }}</flux:button></div>
        </form>
    </flux:card>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:select wire:model.live="status" :label="__('Status')" class="max-w-56">
            @foreach (\App\Enums\QuoteStatus::cases() as $case)
                <flux:select.option :value="$case->value" wire:key="status-{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete this quote request? This cannot be undone.') }}">{{ __('Delete') }}</flux:button>
    </div>
</div>
