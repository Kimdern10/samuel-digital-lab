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

    /**
     * Load the request.
     */
    public function mount(QuoteRequest $quoteRequest): void
    {
        $this->quoteRequest = $quoteRequest;
        $this->status = $quoteRequest->status->value;
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
        $message = "Hello {$this->quoteRequest->name}, this is {$business}. Thank you for your request for a {$this->quoteRequest->service}. When is a good time for a quick chat about it?";

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

    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:select wire:model.live="status" :label="__('Status')" class="max-w-56">
            @foreach (\App\Enums\QuoteStatus::cases() as $case)
                <flux:select.option :value="$case->value" wire:key="status-{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete this quote request? This cannot be undone.') }}">{{ __('Delete') }}</flux:button>
    </div>
</div>
