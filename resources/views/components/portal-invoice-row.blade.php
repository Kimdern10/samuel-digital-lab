@props(['invoice'])

<a href="{{ route('portal.invoices.show', $invoice) }}" wire:navigate {{ $attributes->class('flex flex-wrap items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40') }}>
    <div class="min-w-0">
        <div class="font-medium">{{ $invoice->title }}</div>
        <div class="text-sm text-zinc-500">
            {{ $invoice->number }}
            @if ($invoice->status === \App\Enums\InvoiceStatus::Unpaid && $invoice->due_on)
                · {{ __('Due :date', ['date' => $invoice->due_on->format('j M Y')]) }}
            @elseif ($invoice->paid_at)
                · {{ __('Paid :date', ['date' => $invoice->paid_at->format('j M Y')]) }}
            @endif
        </div>
    </div>
    <div class="flex items-center gap-3">
        <span class="font-medium">{{ \App\Models\Invoice::naira($invoice->amount) }}</span>
        <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
    </div>
</a>
