<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $status = '';

    public string $bankDetails = '';

    /**
     * Load the bank details shown on unpaid invoices.
     */
    public function mount(): void
    {
        $this->bankDetails = Setting::get('bank_details');
    }

    /**
     * Invoices, newest first, filtered by status.
     *
     * @return LengthAwarePaginator<int, Invoice>
     */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::query()
            ->with('client')
            ->when(InvoiceStatus::tryFrom($this->status), fn ($query, InvoiceStatus $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20);
    }

    /**
     * Money still owed and money collected this month, in kobo.
     *
     * @return array{owed: int, collected: int}
     */
    #[Computed]
    public function totals(): array
    {
        return [
            'owed' => (int) Invoice::query()->where('status', InvoiceStatus::Unpaid)->sum('amount'),
            'collected' => (int) Invoice::query()->where('status', InvoiceStatus::Paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
        ];
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Save the bank details clients see when paying by transfer.
     */
    public function saveBankDetails(): void
    {
        $this->validate(['bankDetails' => ['nullable', 'string', 'max:500']]);

        Setting::put('bank_details', trim($this->bankDetails));

        Flux::toast(variant: 'success', text: __('Bank details saved.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Invoices') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Clients pay in their portal with Paystack or by bank transfer, and get a PDF receipt.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.invoices.create')" wire:navigate>{{ __('New invoice') }}</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Still owed') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ \App\Models\Invoice::naira($this->totals['owed'], false) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text>{{ __('Collected this month') }}</flux:text>
            <div class="mt-1 text-2xl font-semibold">{{ \App\Models\Invoice::naira($this->totals['collected'], false) }}</div>
        </div>
    </div>

    @unless (\App\Support\Paystack::enabled())
        <flux:callout icon="information-circle" color="amber">
            <flux:callout.heading>{{ __('Online payment is off') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Add PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY to your .env file to let clients pay by card or bank with Paystack. Until then they see your bank details below.') }}</flux:callout.text>
        </flux:callout>
    @endunless

    <div class="flex flex-wrap items-center gap-3">
        <flux:radio.group wire:model.live="status" variant="segmented" size="sm">
            <flux:radio value="" :label="__('All')" />
            @foreach (\App\Enums\InvoiceStatus::cases() as $case)
                <flux:radio :value="$case->value" :label="$case->label()" />
            @endforeach
        </flux:radio.group>
    </div>

    @if ($this->invoices->isEmpty())
        <flux:callout icon="banknotes">{{ __('No invoices here yet.') }}</flux:callout>
    @else
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->invoices as $invoice)
                <a wire:key="invoice-{{ $invoice->id }}" href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $invoice->number }} · {{ $invoice->title }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $invoice->client->name }}
                            @if ($invoice->status === \App\Enums\InvoiceStatus::Unpaid && $invoice->due_on)
                                · <span @class(['text-red-500' => $invoice->due_on->isPast()])>{{ __('Due :date', ['date' => $invoice->due_on->format('j M Y')]) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-medium">{{ \App\Models\Invoice::naira($invoice->amount) }}</span>
                        <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
                    </div>
                </a>
            @endforeach
        </div>
        {{ $this->invoices->links() }}
    @endif

    <form wire:submit="saveBankDetails" class="grid max-w-xl gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Bank transfer details') }}</flux:heading>
        <flux:textarea wire:model="bankDetails" rows="3" :label="__('Shown on unpaid invoices, PDFs and invoice emails')" :placeholder="__('e.g. Bank: GTBank · Account name: Samuel Digital Lab · Account number: 0123456789')" />
        <div class="flex justify-end"><flux:button variant="primary" type="submit">{{ __('Save bank details') }}</flux:button></div>
    </form>
</div>
