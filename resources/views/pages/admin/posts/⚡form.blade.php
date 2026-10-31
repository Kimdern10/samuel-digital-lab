<?php

use App\Models\Post;
use App\Models\Work;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Article')] class extends Component {
    use WithFileUploads;

    public ?Post $post = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $category = '';

    public string $body = '';

    public string $seo_description = '';

    public bool $is_published = false;

    public string $published_at = '';

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public bool $removeCover = false;

    public bool $slugWasEdited = false;

    public bool $showPreview = false;

    /**
     * Fill the form when editing an existing article.
     */
    public function mount(?Post $post = null): void
    {
        if (! $post?->exists) {
            $this->published_at = now()->format('Y-m-d');

            return;
        }

        $this->post = $post;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->excerpt = (string) $post->excerpt;
        $this->category = (string) $post->category;
        $this->body = $post->body;
        $this->seo_description = (string) $post->seo_description;
        $this->is_published = $post->is_published;
        $this->published_at = ($post->published_at ?? $post->created_at ?? now())->format('Y-m-d');
        $this->slugWasEdited = true;
    }

    /**
     * Keep the web address in step with the title until it is edited by hand.
     */
    public function updatedTitle(): void
    {
        if (! $this->slugWasEdited) {
            $this->slug = Str::slug($this->title);
        }
    }

    /**
     * Remember that the owner chose their own web address.
     */
    public function updatedSlug(): void
    {
        $this->slugWasEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    /**
     * Discard a newly chosen cover, or mark the saved cover for removal when the article is saved.
     */
    public function removeCoverImage(): void
    {
        if ($this->cover) {
            $this->reset('cover');

            return;
        }

        $this->removeCover = true;
    }

    /**
     * Keep the saved cover after all.
     */
    public function keepCover(): void
    {
        $this->removeCover = false;
    }

    /**
     * Save the article.
     */
    public function save(ImageOptimizer $images): void
    {
        $this->slug = Str::slug($this->slug ?: $this->title);

        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash', 'max:170', Rule::unique('posts', 'slug')->ignore($this->post?->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'category' => ['nullable', 'string', 'max:60'],
            'body' => ['required', 'string', 'max:60000'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['boolean'],
            'published_at' => ['required', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'body.required' => 'Write the article before saving.',
            'slug.alpha_dash' => 'Use only letters, numbers and dashes.',
        ]);

        $post = $this->post ?? new Post;
        $oldCover = $post->cover_path;
        $coverPath = $this->removeCover ? null : $oldCover;

        if ($this->cover) {
            $coverPath = $images->store($this->cover, 'posts');
        }

        $publishedAt = Carbon::parse($this->published_at);

        if ($publishedAt->isToday()) {
            $publishedAt = $post->published_at?->isToday() ? $post->published_at : now();
        }

        $post->fill([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?: null,
            'category' => trim($this->category) ?: null,
            'body' => $this->body,
            'seo_description' => $this->seo_description ?: null,
            'is_published' => $this->is_published,
            'published_at' => $publishedAt,
            'cover_path' => $coverPath,
        ])->save();

        if ($oldCover && $oldCover !== $coverPath) {
            Storage::disk('public')->delete([$oldCover, Work::thumbPath($oldCover)]);
        }

        Flux::toast(variant: 'success', text: $post->is_published ? __('Article saved and published.') : __('Article saved as a draft.'));

        $this->redirectRoute('admin.posts.index', navigate: true);
    }

    /**
     * The article as it will look on the website.
     */
    public function previewHtml(): string
    {
        return (new Post(['body' => $this->body]))->bodyHtml()->toHtml();
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('admin.posts.index')" wire:navigate class="text-sm">← {{ __('All articles') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ $post ? __('Edit article') : __('Write an article') }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="grid gap-5">
            <flux:input wire:model.live.debounce.400ms="title" :label="__('Title')" :placeholder="__('e.g. How much does a website cost in Nigeria?')" required />
            <flux:input wire:model.blur="slug" :label="__('Web address')" :description="url('blog').'/'.($slug ?: '…')" />
            <flux:textarea wire:model="excerpt" :label="__('Short summary')" :description="__('One or two sentences shown on the blog page and when shared.')" rows="2" />
            <flux:input wire:model="category" :label="__('Topic')" :description="__('Groups articles on the Blog page, e.g. Website prices, Google and SEO, Tips.')" list="post-topics" class="max-w-80" />
            <datalist id="post-topics">
                @foreach (\App\Models\Post::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->merge(['Website prices', 'Google and SEO', 'Online business tips', 'Our work'])->unique() as $topic)
                    <option value="{{ $topic }}"></option>
                @endforeach
            </datalist>
        </flux:card>

        <flux:card class="grid gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('Article') }}</flux:heading>
                <flux:button size="sm" :icon="$showPreview ? 'pencil-square' : 'eye'" wire:click="$toggle('showPreview')">{{ $showPreview ? __('Back to writing') : __('Preview') }}</flux:button>
            </div>

            @if ($showPreview)
                <div class="prose max-w-none rounded-lg border border-zinc-200 p-5 text-[15px] leading-7 dark:border-zinc-700 [&_a]:text-[#0b7a4b] [&_h2]:mt-6 [&_h2]:text-2xl [&_h2]:font-bold [&_h3]:mt-4 [&_h3]:text-xl [&_h3]:font-bold [&_li]:ms-5 [&_ol]:list-decimal [&_p]:mt-3 [&_table]:mt-3 [&_td]:border [&_td]:border-zinc-200 [&_td]:px-3 [&_td]:py-1.5 [&_th]:border [&_th]:border-zinc-200 [&_th]:px-3 [&_th]:py-1.5 [&_ul]:list-disc">
                    {!! $this->previewHtml() !!}
                </div>
            @else
                <flux:textarea wire:model="body" rows="18" class="font-mono text-sm" :placeholder="__('Start writing here…')" />
                <flux:text class="text-sm">
                    {{ __('Formatting:') }}
                    <code>## Heading</code> · <code>**bold**</code> · <code>- list item</code> · <code>1. numbered item</code> · <code>[link text](https://...)</code> · <code>> quote</code>.
                    {{ __('Leave an empty line between paragraphs.') }}
                </flux:text>
            @endif
            @error('body') <flux:text class="text-red-600! dark:text-red-400!">{{ $message }}</flux:text> @enderror
        </flux:card>

        <flux:card class="grid gap-4">
            <flux:heading size="lg">{{ __('Cover picture') }}</flux:heading>
            <flux:input type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp" :description="__('Optional. Shown at the top of the article and when it is shared on WhatsApp or Facebook.')" />
            <div wire:loading wire:target="cover"><flux:text>{{ __('Uploading…') }}</flux:text></div>
            @if ($cover && $cover->isPreviewable() && ! $errors->has('cover'))
                <div class="relative w-72">
                    <img src="{{ $cover->temporaryUrl() }}" alt="" class="aspect-[16/9] w-full rounded-lg border border-amber-400 bg-zinc-50 object-contain dark:bg-zinc-800">
                    <div class="absolute end-2 top-2"><flux:button size="xs" variant="danger" icon="x-mark" wire:click="removeCoverImage" :aria-label="__('Remove cover picture')" /></div>
                </div>
            @elseif ($post?->cover_path && ! $removeCover)
                <div class="relative w-72">
                    <img src="{{ $post->coverThumbUrl() }}" alt="" class="aspect-[16/9] w-full rounded-lg border border-zinc-200 bg-zinc-50 object-contain dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="absolute end-2 top-2"><flux:button size="xs" variant="danger" icon="x-mark" wire:click="removeCoverImage" :aria-label="__('Remove cover picture')" /></div>
                </div>
            @elseif ($removeCover)
                <flux:callout icon="trash" color="amber" class="max-w-lg">
                    <flux:callout.text>{{ __('The cover will be deleted when you save.') }} <flux:link class="cursor-pointer" wire:click="keepCover">{{ __('Undo') }}</flux:link></flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Publishing') }}</flux:heading>
            <flux:switch wire:model="is_published" :label="__('Published')" :description="__('Turn on to show it on your website. Leave off to keep it as a draft.')" />
            <flux:input wire:model="published_at" type="date" :label="__('Date')" :description="__('A future date schedules the article: it appears on that day.')" class="max-w-56" />
            <flux:textarea wire:model="seo_description" :label="__('Google description')" :description="__('About 150 characters shown in Google results. Leave empty to use the summary.')" rows="2" />
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ __('Save article') }}</flux:button>
            <flux:button :href="route('admin.posts.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
