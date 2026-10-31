<?php

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The blog list with live search and topic filters. Results update as the visitor types,
 * and the search stays in the address bar so it can be shared or bookmarked.
 */
new class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    /**
     * Ignore a topic in the address bar that no article uses.
     */
    public function mount(): void
    {
        $this->q = mb_substr($this->q, 0, 80);

        if (! $this->categories->contains($this->category)) {
            $this->category = '';
        }
    }

    /**
     * Topics used by published articles.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Post::query()->published()->reorder()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
    }

    /**
     * Published articles matching the search and topic.
     *
     * @return LengthAwarePaginator<int, Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        $search = trim(mb_substr($this->q, 0, 80));
        $category = $this->categories->contains($this->category) ? $this->category : null;

        return Post::query()
            ->published()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $query->where(fn ($query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->paginate(12);
    }

    /**
     * Go back to the first page whenever the search changes.
     */
    public function updatedQ(): void
    {
        $this->q = mb_substr($this->q, 0, 80);
        $this->resetPage();
    }

    /**
     * Show one topic, or every topic when given an empty string.
     */
    public function filter(string $category): void
    {
        $this->category = $this->categories->contains($category) ? $category : '';
        $this->resetPage();
    }

    /**
     * Clear the search and the topic.
     */
    public function clear(): void
    {
        $this->reset('q', 'category');
        $this->resetPage();
    }
}; ?>

<div class="blog-live">
    <div class="blog-tools">
        {{-- Without JavaScript the form still works as a normal search. --}}
        <form class="blog-search" action="{{ route('blog.index') }}" method="get" role="search" wire:submit="$refresh">
            @if ($category !== '')
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <label class="sr-only" for="blog-q">Search articles</label>
            <x-site-icon name="search" />
            <input id="blog-q" type="search" name="q" wire:model.live.debounce.350ms="q" placeholder="Search articles…" maxlength="80" autocomplete="off">
            <span class="blog-search__spinner" wire:loading aria-hidden="true"></span>
            <button type="submit" class="btn btn--primary btn--sm">Search</button>
        </form>

        @if ($this->categories->isNotEmpty())
            <nav class="filters" aria-label="Filter by topic">
                <a href="{{ route('blog.index', array_filter(['q' => $q])) }}" wire:click.prevent="filter('')" @class(['filter', 'is-active' => $category === '']) @if ($category === '') aria-current="page" @endif>All topics</a>
                @foreach ($this->categories as $option)
                    <a href="{{ route('blog.index', array_filter(['category' => $option, 'q' => $q])) }}" wire:key="topic-{{ $loop->index }}" wire:click.prevent="filter(@js($option))" @class(['filter', 'is-active' => $category === $option]) @if ($category === $option) aria-current="page" @endif>{{ $option }}</a>
                @endforeach
            </nav>
        @endif
    </div>

    <div class="blog-live__results" wire:loading.class="is-loading">
        @if (trim($q) !== '' || $category !== '')
            <p class="blog-results" role="status">
                {{ trans_choice('{0} No articles|{1} 1 article|[2,*] :count articles', $this->posts->total()) }}
                @if (trim($q) !== '') for “{{ trim($q) }}”@endif
                @if ($category !== '') in {{ $category }}@endif
                · <a class="link" href="{{ route('blog.index') }}" wire:click.prevent="clear">Clear</a>
            </p>
        @endif

        @if ($this->posts->isEmpty())
            <p class="empty">{{ trim($q) !== '' || $category !== '' ? 'Try another word or topic.' : 'New articles are on the way. Check back soon.' }}</p>
        @else
            <div class="work-grid">
                @foreach ($this->posts as $post)
                    <x-post-card :post="$post" heading-level="h2" wire:key="post-{{ $post->id }}" class="post-card--live" style="--delay: {{ ($loop->index % 3) * 0.06 }}s" />
                @endforeach
            </div>
            @if ($this->posts->hasPages())
                <nav class="pager" aria-label="Pages">
                    @if (! $this->posts->onFirstPage())
                        <a class="btn btn--outline btn--sm" href="{{ $this->posts->previousPageUrl() }}" wire:click.prevent="previousPage" x-on:click="$el.closest('.blog-live').scrollIntoView({ behavior: 'smooth' })">← Newer</a>
                    @endif
                    @if ($this->posts->hasMorePages())
                        <a class="btn btn--outline btn--sm" href="{{ $this->posts->nextPageUrl() }}" wire:click.prevent="nextPage" x-on:click="$el.closest('.blog-live').scrollIntoView({ behavior: 'smooth' })">Older →</a>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</div>
