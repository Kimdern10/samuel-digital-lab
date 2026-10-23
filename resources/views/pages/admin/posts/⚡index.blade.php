<?php

use App\Models\Post;
use App\Models\Work;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Blog')] class extends Component {
    /**
     * Every article, newest first.
     *
     * @return Collection<int, Post>
     */
    #[Computed]
    public function posts(): Collection
    {
        return Post::query()->orderByDesc('published_at')->orderByDesc('id')->get();
    }

    /**
     * Publish an article, or move it back to drafts.
     */
    public function togglePublished(int $id): void
    {
        $post = Post::query()->findOrFail($id);
        $post->update([
            'is_published' => ! $post->is_published,
            'published_at' => $post->published_at ?? now(),
        ]);

        Flux::toast(variant: 'success', text: $post->is_published ? __('Article published.') : __('Moved to drafts.'));
    }

    /**
     * Delete an article and its cover.
     */
    public function delete(int $id): void
    {
        $post = Post::query()->findOrFail($id);

        if ($post->cover_path) {
            Storage::disk('public')->delete([$post->cover_path, Work::thumbPath($post->cover_path)]);
        }

        $post->delete();

        Flux::toast(variant: 'success', text: __('Article deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Blog') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Helpful articles bring visitors from Google. One good article a month is a great start.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.posts.create')" wire:navigate>{{ __('Write an article') }}</flux:button>
    </div>

    @if ($this->posts->isEmpty())
        <flux:callout icon="newspaper">{{ __('No articles yet. The Blog link appears on your website once you publish one.') }}</flux:callout>
    @else
        <div class="grid gap-3">
            @foreach ($this->posts as $post)
                <div wire:key="post-{{ $post->id }}" class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    @if ($post->cover_path)
                        <img src="{{ $post->coverThumbUrl() }}" alt="" class="h-16 w-24 flex-none rounded-lg object-cover">
                    @else
                        <div class="flex h-16 w-24 flex-none items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800"><flux:icon.newspaper class="size-6 text-zinc-400" /></div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ $post->title }}</span>
                            @if (! $post->is_published)
                                <flux:badge size="sm" color="zinc">{{ __('Draft') }}</flux:badge>
                            @elseif ($post->published_at?->isFuture())
                                <flux:badge size="sm" color="sky">{{ __('Scheduled') }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">{{ __('Published') }}</flux:badge>
                            @endif
                        </div>
                        <div class="text-sm text-zinc-500">{{ ($post->published_at ?? $post->created_at)?->format('j M Y') }} · {{ $post->readingMinutes() }} {{ __('min read') }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" icon="pencil-square" :href="route('admin.posts.edit', $post)" wire:navigate>{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" wire:click="togglePublished({{ $post->id }})">{{ $post->is_published ? __('Unpublish') : __('Publish') }}</flux:button>
                        @if ($post->is_published)
                            <flux:button size="sm" icon="arrow-top-right-on-square" :href="route('blog.show', $post)" target="_blank">{{ __('View') }}</flux:button>
                        @endif
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $post->id }})" wire:confirm="{{ __('Delete this article? This cannot be undone.') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
