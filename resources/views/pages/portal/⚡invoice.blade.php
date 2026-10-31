<?php

use App\Models\Invoice;
use App\Models\Setting;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public Invoice $invoice;

    public string $bankDetails = '';

    /**
     * Show the invoice, but only to the client it belongs to.
     */
    public function mount(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 404);

        $this->invoice = $invoice;
        $this->bankDetails = Setting::get('bank_details');
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('portal.invoices')" wire:navigate class="text-sm">← {{ __('All invoices') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">
            {{ $invoice->title }}
            <flux:badge :color="$invoice->status->color()" class="ms-2 align-middle">{{ $invoice->status->label() }}</flux:badge>
        </flux:heading>
        <flux:text class="mt-1">{{ $invoice->number }}@if ($invoice->project) · {{ $invoice->project->title }}@endif</flux:text>
    </div>

    @if (session('payment_success'))
        <flux:callout icon="check-circle" color="green">{{ session('payment_success') }}</flux:callout>
    @endif
    @if (session('payment_error'))
        <flux:callout icon="exclamation-triangle" color="red">{{ session('payment_error') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        @foreach ($invoice->items as $item)
            <div class="flex justify-between gap-4 border-b border-zinc-200 p-4 dark:border-zinc-700">
                <span>{{ $item['description'] }}</span>
                <span class="whitespace-nowrap">{{ \App\Models\Invoice::naira($item['amount']) }}</span>
            </div>
        @endforeach
        <div class="flex justify-between gap-4 p-4 text-lg font-semibold">
            <span>{{ $invoice->isPayable() ? __('Total to pay') : __('Total') }}</span>
            <span class="whitespace-nowrap">{{ \App\Models\Invoice::naira($invoice->amount) }}</span>
        </div>
    </div>

    @if ($invoice->notes)
        <flux:text class="whitespace-pre-line">{{ $invoice->notes }}</flux:text>
    @endif

    @if ($invoice->isPayable())
        <div class="grid gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            @if ($invoice->due_on)
                <flux:text>{{ __('Please pay by :date.', ['date' => $invoice->due_on->format('j F Y')]) }}</flux:text>
            @endif
            @if (\App\Support\Paystack::enabled())
                <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}">
                    @csrf
                    <flux:button type="submit" variant="primary" icon="credit-card">{{ __('Pay :amount with Paystack', ['amount' => \App\Models\Invoice::naira($invoice->amount, false)]) }}</flux:button>
                </form>
                <flux:text class="text-sm">{{ __('Card, bank transfer or USSD. Your receipt is emailed as soon as the payment goes through.') }}</flux:text>
            @endif
            @if (filled($bankDetails))
                <div>
                    <flux:heading>{{ \App\Support\Paystack::enabled() ? __('Or pay by bank transfer') : __('Pay by bank transfer') }}</flux:heading>
                    <flux:text class="mt-1 whitespace-pre-line">{{ $bankDetails }}</flux:text>
                    <flux:text class="mt-1">{{ __('Use :number as the payment description. We will send your receipt once it arrives.', ['number' => $invoice->number]) }}</flux:text>
                </div>
            @elseif (! \App\Support\Paystack::enabled())
                <flux:text>{{ __('Message us on WhatsApp for payment details.') }}</flux:text>
            @endif
        </div>
    @elseif ($invoice->paid_at)
        <flux:text>{{ __('Paid on :date. Thank you!', ['date' => $invoice->paid_at->format('j F Y')]) }}</flux:text>
    @endif

    <div>
        <flux:button icon="arrow-down-tray" :href="route('portal.invoices.pdf', $invoice)">{{ $invoice->paid_at ? __('Download receipt (PDF)') : __('Download invoice (PDF)') }}</flux:button>
    </div>
</div>
