<?php

use App\Models\Work;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Our work')] class extends Component {
    /**
     * Every work, in the order shown on the site.
     *
     * @return Collection<int, Work>
     */
    #[Computed]
    public function works(): Collection
    {
        return Work::query()->ordered()->get();
    }

    /**
     * Show or hide a work on the website.
     */
    public function togglePublished(int $id): void
    {
        $work = Work::query()->findOrFail($id);
        $work->update(['is_published' => ! $work->is_published]);

        Flux::toast(variant: 'success', text: $work->is_published ? __('Published.') : __('Moved to drafts.'));
    }

    /**
     * Delete a work and its images.
     */
    public function delete(int $id): void
    {
        $work = Work::query()->findOrFail($id);
        Storage::disk('public')->delete($work->imagePaths());
        $work->delete();

        Flux::toast(variant: 'success', text: __('Work deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Our work') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Projects shown on your website. Lower sort numbers show first.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.works.create')" wire:navigate>{{ __('Add a work') }}</flux:button>
    </div>

    @if ($this->works->isEmpty())
        <flux:callout icon="photo">{{ __('No works yet. Add your first project with a cover image and screenshots.') }}</flux:callout>
    @else
        <div class="grid gap-3">
            @foreach ($this->works as $work)
                <div wire:key="work-{{ $work->id }}" class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    @if ($preview = $work->previewImageUrl())
                        <img src="{{ $preview }}" alt="" class="h-16 w-24 flex-none rounded-lg object-cover object-top">
                    @else
                        <div class="flex h-16 w-24 flex-none items-center justify-center rounded-lg bg-zinc-100 text-xs text-zinc-500 dark:bg-zinc-800">{{ __('No cover') }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ $work->title }}</span>
                            @if ($work->is_published)
                                <flux:badge size="sm" color="green">{{ __('Published') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Draft') }}</flux:badge>
                            @endif
                            @if ($work->is_featured)
                                <flux:badge size="sm" color="amber">{{ __('On home page') }}</flux:badge>
                            @endif
                        </div>
                        <div class="text-sm text-zinc-500">{{ $work->category ?: __('No category') }} · {{ __('Sort') }} {{ $work->sort_order }} · {{ count($work->screenshots ?? []) }} {{ __('screenshots') }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" icon="pencil-square" :href="route('admin.works.edit', $work)" wire:navigate>{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" wire:click="togglePublished({{ $work->id }})">{{ $work->is_published ? __('Unpublish') : __('Publish') }}</flux:button>
                        @if ($work->is_published)
                            <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('work.show', $work)" target="_blank">{{ __('View') }}</flux:button>
                        @endif
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $work->id }})" wire:confirm="{{ __('Delete this work and its images? This cannot be undone.') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
