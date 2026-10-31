<?php

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoices')] class extends Component {
    /**
     * The client's invoices, newest first.
     *
     * @return Collection<int, Invoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        return auth()->user()->invoices()->get();
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Invoices') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Pay online and download your receipts.') }}</flux:text>
    </div>

    @if (session('payment_error'))
        <flux:callout icon="exclamation-triangle" color="red">{{ session('payment_error') }}</flux:callout>
    @endif

    @forelse ($this->invoices as $invoice)
        <x-portal-invoice-row :invoice="$invoice" wire:key="invoice-{{ $invoice->id }}" />
    @empty
        <flux:callout icon="banknotes">{{ __('You have no invoices yet.') }}</flux:callout>
    @endforelse
</div>
