<?php

use App\Enums\QuoteStatus;
use App\Models\QuoteRequest;
use App\Models\Work;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * Count the things the owner cares about at a glance.
     *
     * @return array{new_quotes: int, total_quotes: int, published_works: int, draft_works: int}
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'new_quotes' => QuoteRequest::query()->where('status', QuoteStatus::New)->count(),
            'total_quotes' => QuoteRequest::query()->count(),
            'published_works' => Work::query()->published()->count(),
            'draft_works' => Work::query()->where('is_published', false)->count(),
        ];
    }

    /**
     * The latest quote requests.
     *
     * @return Collection<int, QuoteRequest>
     */
    #[Computed]
    public function latestQuotes(): Collection
    {
        return QuoteRequest::query()->latest()->take(5)->get();
    }
}; ?>

<div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text class="mt-1">{{ __('Here is what is happening on your website.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.works.create')" wire:navigate>{{ __('Add a work') }}</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:text>{{ __('New quote requests') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['new_quotes'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('All quote requests') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['total_quotes'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Published works') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['published_works'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Drafts') }}</flux:text>
            <flux:heading size="xl" class="mt-2 font-mono">{{ $this->stats['draft_works'] }}</flux:heading>
        </flux:card>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between">
            <flux:heading size="lg" level="2">{{ __('Latest quote requests') }}</flux:heading>
            <flux:link :href="route('admin.quotes.index')" wire:navigate>{{ __('See all') }}</flux:link>
        </div>

        @if ($this->latestQuotes->isEmpty())
            <flux:callout icon="inbox">{{ __('No quote requests yet. They will appear here when visitors use the form on your home page.') }}</flux:callout>
        @else
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($this->latestQuotes as $quote)
                    <a href="{{ route('admin.quotes.show', $quote) }}" wire:navigate wire:key="quote-{{ $quote->id }}" class="flex items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ $quote->name }} @if ($quote->business)<span class="text-zinc-500">· {{ $quote->business }}</span>@endif</div>
                            <div class="truncate text-sm text-zinc-500">{{ $quote->service }} · {{ $quote->created_at->diffForHumans() }}</div>
                        </div>
                        <flux:badge size="sm" :color="$quote->status->color()">{{ $quote->status->label() }}</flux:badge>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
