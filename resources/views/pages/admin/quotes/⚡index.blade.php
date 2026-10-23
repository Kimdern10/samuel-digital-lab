<?php

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quote requests')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    /**
     * Reset to the first page when the filter changes.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * The quote requests for the chosen status, newest first.
     *
     * @return LengthAwarePaginator<int, QuoteRequest>
     */
    #[Computed]
    public function quotes(): LengthAwarePaginator
    {
        return QuoteRequest::query()
            ->when(QuoteStatus::tryFrom($this->status), fn ($query, QuoteStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }

    /**
     * How many requests have each status.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return QuoteRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($total): int => (int) $total)->all();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Quote requests') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Requests sent from the form on your home page.') }}</flux:text>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" :variant="$status === '' ? 'primary' : 'outline'" wire:click="$set('status', '')">{{ __('All') }} ({{ array_sum($this->counts) }})</flux:button>
        @foreach (\App\Enums\QuoteStatus::cases() as $case)
            <flux:button size="sm" :variant="$status === $case->value ? 'primary' : 'outline'" wire:click="$set('status', '{{ $case->value }}')" wire:key="filter-{{ $case->value }}">
                {{ $case->label() }} ({{ $this->counts[$case->value] ?? 0 }})
            </flux:button>
        @endforeach
    </div>

    @if ($this->quotes->isEmpty())
        <flux:callout icon="inbox">{{ __('No quote requests here yet.') }}</flux:callout>
    @else
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->quotes as $quote)
                <a href="{{ route('admin.quotes.show', $quote) }}" wire:navigate wire:key="quote-{{ $quote->id }}" class="flex items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ $quote->name }} @if ($quote->business)<span class="font-normal text-zinc-500">· {{ $quote->business }}</span>@endif</div>
                        <div class="truncate text-sm text-zinc-500">{{ $quote->service }} · {{ $quote->budget }} · {{ $quote->created_at->format('j M Y, g:ia') }}</div>
                    </div>
                    <flux:badge size="sm" :color="$quote->status->color()">{{ $quote->status->label() }}</flux:badge>
                </a>
            @endforeach
        </div>

        {{ $this->quotes->links() }}
    @endif
</div>
