<?php

use App\Models\Subscriber;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Newsletter')] class extends Component {
    use WithPagination;

    /**
     * Subscribers, newest first.
     *
     * @return LengthAwarePaginator<int, Subscriber>
     */
    #[Computed]
    public function subscribers(): LengthAwarePaginator
    {
        return Subscriber::query()->latest()->paginate(30);
    }

    /**
     * How many people are still subscribed.
     */
    #[Computed]
    public function activeCount(): int
    {
        return Subscriber::query()->active()->count();
    }

    /**
     * Remove someone from the list completely.
     */
    public function delete(int $id): void
    {
        Subscriber::query()->findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Subscriber removed.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Newsletter') }}</flux:heading>
            <flux:text class="mt-1">{{ __(':count people are subscribed. Download the list to send updates with Gmail, Mailchimp or Brevo; each row includes their unsubscribe link.', ['count' => $this->activeCount]) }}</flux:text>
        </div>
        <flux:button variant="primary" icon="arrow-down-tray" :href="route('admin.subscribers.export')">{{ __('Download CSV') }}</flux:button>
    </div>

    @if ($this->subscribers->isEmpty())
        <flux:callout icon="envelope">{{ __('No subscribers yet. Visitors can sign up at the bottom of every page.') }}</flux:callout>
    @else
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->subscribers as $subscriber)
                <div wire:key="subscriber-{{ $subscriber->id }}" class="flex items-center justify-between gap-4 p-3">
                    <div class="min-w-0">
                        <div class="truncate font-medium">{{ $subscriber->email }}</div>
                        <div class="text-sm text-zinc-500">{{ __('Joined :date', ['date' => $subscriber->created_at?->format('j M Y')]) }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($subscriber->unsubscribed_at)
                            <flux:badge size="sm" color="zinc">{{ __('Unsubscribed') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="green">{{ __('Subscribed') }}</flux:badge>
                        @endif
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $subscriber->id }})" wire:confirm="{{ __('Remove this subscriber completely?') }}" :aria-label="__('Remove')" />
                    </div>
                </div>
            @endforeach
        </div>
        {{ $this->subscribers->links() }}
    @endif
</div>
