<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Support\InvoiceMailer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public ?Invoice $invoice = null;

    public string $clientId = '';

    public string $projectId = '';

    public string $title = '';

    /** @var list<array{description: string, amount: string}> */
    public array $items = [];

    public string $dueOn = '';

    public string $notes = '';

    public string $paymentMethod = 'bank_transfer';

    public string $paymentReference = '';

    /**
     * Load the invoice, or start a new one (optionally for a project from ?project=).
     */
    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice?->exists) {
            $this->invoice = $invoice;
            $this->clientId = (string) $invoice->user_id;
            $this->projectId = (string) $invoice->project_id;
            $this->title = $invoice->title;
            $this->items = array_map(fn (array $item): array => [
                'description' => $item['description'],
                'amount' => rtrim(rtrim(number_format($item['amount'] / 100, 2, '.', ''), '0'), '.'),
            ], $invoice->items);
            $this->dueOn = (string) $invoice->due_on?->format('Y-m-d');
            $this->notes = (string) $invoice->notes;

            return;
        }

        $project = Project::query()->find(request()->integer('project'));
        $this->clientId = (string) $project?->user_id;
        $this->projectId = (string) $project?->id;
        $this->title = $project ? __('Website deposit (50%)') : '';
        $this->items = [['description' => $project ? $project->title.': 50% deposit' : '', 'amount' => '']];
        $this->dueOn = now()->addWeek()->format('Y-m-d');
    }

    /**
     * Clients the invoice can be for.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function clients(): Collection
    {
        return User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * The chosen client's projects.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->where('user_id', (int) $this->clientId)->latest('id')->get(['id', 'title']);
    }

    /**
     * Clear the project when another client is picked.
     */
    public function updatedClientId(): void
    {
        $this->projectId = '';
    }

    /**
     * Whether the invoice can still be changed (paid and cancelled ones are locked).
     */
    #[Computed]
    public function isEditable(): bool
    {
        return $this->invoice === null || $this->invoice->status === InvoiceStatus::Unpaid;
    }

    /**
     * The total of the line items, in kobo.
     */
    #[Computed]
    public function total(): int
    {
        return array_sum(array_map(fn (array $item): int => $this->toKobo($item['amount']), $this->items));
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'amount' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Save the invoice, and email it to the client when asked.
     */
    public function save(bool $send = false): void
    {
        abort_unless($this->isEditable, 403);

        // Accept amounts typed like "₦150,000".
        $this->items = array_map(fn (array $item): array => [
            'description' => (string) $item['description'],
            'amount' => str_replace([',', '₦', ' '], '', (string) $item['amount']),
        ], $this->items);

        $this->validate([
            'clientId' => ['required', Rule::exists('users', 'id')->where('is_admin', false)],
            'projectId' => ['nullable', Rule::exists('projects', 'id')->where('user_id', (int) $this->clientId)],
            'title' => ['required', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'dueOn' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'clientId' => 'client',
            'items.*.description' => 'item description',
            'items.*.amount' => 'item amount',
        ]);

        if ($this->total <= 0) {
            $this->addError('items', __('The total must be more than ₦0.'));

            return;
        }

        $attributes = [
            'user_id' => (int) $this->clientId,
            'project_id' => $this->projectId !== '' ? (int) $this->projectId : null,
            'title' => trim($this->title),
            'items' => array_map(fn (array $item): array => [
                'description' => trim($item['description']),
                'amount' => $this->toKobo($item['amount']),
            ], $this->items),
            'amount' => $this->total,
            'due_on' => $this->dueOn ?: null,
            'notes' => trim($this->notes) ?: null,
        ];

        $isNew = $this->invoice === null;
        if ($this->invoice === null) {
            $this->invoice = Invoice::query()->create($attributes);
        } else {
            $this->invoice->update($attributes);
        }

        if ($send) {
            $sent = InvoiceMailer::sendInvoice($this->invoice);
            Flux::toast(variant: $sent ? 'success' : 'danger', text: $sent
                ? __('Invoice :number emailed to :email.', ['number' => $this->invoice->number, 'email' => $this->invoice->client->email])
                : __('Invoice saved, but the email could not be sent. Check your mail settings.'));
        } else {
            Flux::toast(variant: 'success', text: __('Invoice :number saved.', ['number' => $this->invoice->number]));
        }

        if ($isNew) {
            $this->redirectRoute('admin.invoices.edit', $this->invoice, navigate: true);
        }
    }

    /**
     * Record a payment made outside Paystack (bank transfer or cash) and send the receipt.
     */
    public function markPaid(): void
    {
        $this->validate([
            'paymentMethod' => ['required', Rule::in(array_keys(Invoice::PAYMENT_METHODS))],
            'paymentReference' => ['nullable', 'string', 'max:100', Rule::unique('invoices', 'payment_reference')],
        ]);

        if ($this->invoice?->status !== InvoiceStatus::Unpaid) {
            return;
        }

        $this->invoice->markPaid($this->paymentMethod, trim($this->paymentReference) ?: null);
        $sent = InvoiceMailer::sendReceipt($this->invoice);

        Flux::modal('mark-paid')->close();
        unset($this->isEditable);
        Flux::toast(variant: 'success', text: $sent ? __('Marked as paid. The receipt was emailed to the client.') : __('Marked as paid. The receipt email could not be sent.'));
    }

    /**
     * Cancel an unpaid invoice so the client can no longer pay it.
     */
    public function cancel(): void
    {
        if ($this->invoice?->status === InvoiceStatus::Unpaid) {
            $this->invoice->update(['status' => InvoiceStatus::Cancelled]);
            unset($this->isEditable);
            Flux::toast(variant: 'success', text: __('Invoice cancelled.'));
        }
    }

    /**
     * Turn a naira amount typed by the admin (e.g. "150,000.50") into kobo.
     */
    private function toKobo(mixed $naira): int
    {
        $clean = str_replace([',', '₦', ' '], '', (string) $naira);

        return is_numeric($clean) ? (int) round((float) $clean * 100) : 0;
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:link :href="route('admin.invoices.index')" wire:navigate class="text-sm">← {{ __('All invoices') }}</flux:link>
            <flux:heading size="xl" level="1" class="mt-2">
                {{ $invoice ? __('Invoice :number', ['number' => $invoice->number]) : __('New invoice') }}
                @if ($invoice)
                    <flux:badge :color="$invoice->status->color()" class="ms-2 align-middle">{{ $invoice->status->label() }}</flux:badge>
                @endif
            </flux:heading>
            @if ($invoice?->paid_at)
                <flux:text class="mt-1">{{ __('Paid :date by :method', ['date' => $invoice->paid_at->format('j M Y, g:ia'), 'method' => \App\Models\Invoice::PAYMENT_METHODS[$invoice->payment_method] ?? $invoice->payment_method]) }}@if ($invoice->payment_reference) · {{ $invoice->payment_reference }}@endif</flux:text>
            @elseif ($invoice?->sent_at)
                <flux:text class="mt-1">{{ __('Emailed to the client on :date', ['date' => $invoice->sent_at->format('j M Y, g:ia')]) }}</flux:text>
            @endif
        </div>
        @if ($invoice)
            <div class="flex flex-wrap gap-2">
                <flux:button icon="arrow-down-tray" :href="route('admin.invoices.pdf', $invoice)">{{ __('PDF') }}</flux:button>
                @if ($this->isEditable)
                    <flux:modal.trigger name="mark-paid">
                        <flux:button icon="check-circle">{{ __('Mark as paid') }}</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endif
    </div>

    <form wire:submit="save" class="grid gap-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <fieldset @disabled(! $this->isEditable) class="grid gap-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model.live="clientId" :label="__('Client')" :placeholder="__('Choose a client')">
                    @foreach ($this->clients as $client)
                        <flux:select.option :value="$client->id">{{ $client->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="projectId" :label="__('Project (optional)')">
                    <flux:select.option value="">{{ __('No project') }}</flux:select.option>
                    @foreach ($this->projects as $project)
                        <flux:select.option :value="$project->id">{{ $project->title }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:input wire:model="title" :label="__('Invoice title')" :placeholder="__('e.g. Website deposit (50%)')" required />

            <div class="grid gap-3">
                <flux:label>{{ __('Items (amounts in ₦)') }}</flux:label>
                @foreach ($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="flex items-start gap-2">
                        <div class="flex-1"><flux:input wire:model="items.{{ $index }}.description" :placeholder="__('Description')" :aria-label="__('Description')" /></div>
                        <div class="w-36"><flux:input wire:model.blur="items.{{ $index }}.amount" inputmode="decimal" placeholder="150000" :aria-label="__('Amount in naira')" /></div>
                        @if (count($items) > 1)
                            <flux:button variant="ghost" icon="x-mark" wire:click="removeItem({{ $index }})" :aria-label="__('Remove item')" />
                        @endif
                    </div>
                @endforeach
                <flux:error name="items" />
                <flux:error name="items.*.description" />
                <flux:error name="items.*.amount" />
                <div class="flex items-center justify-between">
                    <flux:button size="sm" icon="plus" wire:click="addItem">{{ __('Add item') }}</flux:button>
                    <div class="text-lg font-semibold">{{ __('Total') }}: {{ \App\Models\Invoice::naira($this->total) }}</div>
                </div>
            </div>

            <flux:input wire:model="dueOn" type="date" :label="__('Due date')" />
            <flux:textarea wire:model="notes" rows="3" :label="__('Note to the client (optional)')" :placeholder="__('e.g. The balance is due on launch day.')" />
        </fieldset>

        @if ($this->isEditable)
            <div class="flex flex-wrap justify-between gap-2">
                <div>
                    @if ($invoice)
                        <flux:button variant="ghost" wire:click="cancel" wire:confirm="{{ __('Cancel this invoice? The client will no longer be able to pay it.') }}">{{ __('Cancel invoice') }}</flux:button>
                    @endif
                </div>
                <div class="flex gap-2">
                    <flux:button type="submit">{{ __('Save') }}</flux:button>
                    <flux:button variant="primary" icon="paper-airplane" wire:click="save(true)">{{ $invoice?->sent_at ? __('Save and email again') : __('Save and email to client') }}</flux:button>
                </div>
            </div>
        @endif
    </form>

    @if ($invoice && $this->isEditable)
        <flux:modal name="mark-paid" class="w-full max-w-md">
            <form wire:submit="markPaid" class="grid gap-5">
                <flux:heading size="lg">{{ __('Mark as paid') }}</flux:heading>
                <flux:text>{{ __('Use this when the client paid by bank transfer or cash. Paystack payments are marked paid automatically.') }}</flux:text>
                <flux:select wire:model="paymentMethod" :label="__('How did they pay?')">
                    @foreach (\App\Models\Invoice::PAYMENT_METHODS as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="paymentReference" :label="__('Reference (optional)')" :placeholder="__('e.g. bank transfer session ID')" />
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Mark paid and send receipt') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
