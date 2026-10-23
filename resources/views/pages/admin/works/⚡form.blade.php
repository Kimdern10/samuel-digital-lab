<?php

use App\Models\Work;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Work')] class extends Component {
    use WithFileUploads;

    public ?Work $work = null;

    public string $title = '';

    public string $slug = '';

    public string $client = '';

    public string $category = '';

    public string $summary = '';

    public string $headline = '';

    public string $description = '';

    public string $highlights = '';

    public string $technologies = '';

    /** @var list<array{value: string, label: string}> */
    public array $results = [];

    public string $live_url = '';

    public string $seo_description = '';

    public bool $is_published = true;

    public bool $is_featured = false;

    public int $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newScreenshots = [];

    /** @var list<string> */
    public array $screenshots = [];

    public bool $slugWasEdited = false;

    /**
     * Fill the form when editing an existing work.
     */
    public function mount(?Work $work = null): void
    {
        if (! $work?->exists) {
            $this->results = [['value' => '', 'label' => '']];
            $this->sort_order = (int) Work::query()->max('sort_order') + 1;

            return;
        }

        $this->work = $work;
        $this->fill($work->only([
            'title', 'slug', 'is_published', 'is_featured', 'sort_order',
        ]));
        $this->client = (string) $work->client;
        $this->category = (string) $work->category;
        $this->summary = (string) $work->summary;
        $this->headline = (string) $work->headline;
        $this->description = (string) $work->description;
        $this->live_url = (string) $work->live_url;
        $this->seo_description = (string) $work->seo_description;
        $this->highlights = implode("\n", $work->highlights ?? []);
        $this->technologies = implode(', ', $work->technologies ?? []);
        $this->results = $work->results ?: [['value' => '', 'label' => '']];
        $this->screenshots = $work->screenshots ?? [];
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
     * Add an empty result row.
     */
    public function addResult(): void
    {
        $this->results[] = ['value' => '', 'label' => ''];
    }

    /**
     * Remove a result row.
     */
    public function removeResult(int $index): void
    {
        unset($this->results[$index]);
        $this->results = array_values($this->results);
    }

    /**
     * Remove a saved screenshot (the file is deleted when the work is saved).
     */
    public function removeScreenshot(int $index): void
    {
        unset($this->screenshots[$index]);
        $this->screenshots = array_values($this->screenshots);
    }

    /**
     * Remove a screenshot that has been chosen but not saved yet.
     */
    public function removeNewScreenshot(int $index): void
    {
        unset($this->newScreenshots[$index]);
        $this->newScreenshots = array_values($this->newScreenshots);
    }

    /**
     * Get the validation rules for the form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', 'max:160', Rule::unique('works', 'slug')->ignore($this->work?->id)],
            'client' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:300'],
            'headline' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'highlights' => ['nullable', 'string', 'max:3000'],
            'technologies' => ['nullable', 'string', 'max:500'],
            'results' => ['array', 'max:6'],
            'results.*.value' => ['nullable', 'string', 'max:40'],
            'results.*.label' => ['nullable', 'string', 'max:80'],
            'live_url' => ['nullable', 'url:http,https', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'newScreenshots' => ['array', 'max:'.max(0, Work::MAX_SCREENSHOTS - count($this->screenshots))],
            'newScreenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'newScreenshots.max' => 'A work can have up to '.Work::MAX_SCREENSHOTS.' screenshots. Remove some first.',
            'newScreenshots.*.image' => 'Each screenshot must be an image (JPG, PNG or WebP).',
            'newScreenshots.*.max' => 'Each screenshot must be smaller than 10 MB.',
            'cover.max' => 'The cover image must be smaller than 10 MB.',
            'slug.alpha_dash' => 'Use only letters, numbers and dashes.',
        ];
    }

    /**
     * Save the work, compressing any new images to WebP.
     */
    public function save(ImageOptimizer $images): void
    {
        $this->slug = Str::slug($this->slug ?: $this->title);

        $this->validate();

        $work = $this->work ?? new Work;
        $oldPaths = $work->exists ? $work->imagePaths() : [];

        $coverPath = $work->cover_path;

        if ($this->cover) {
            $coverPath = $images->store($this->cover);
        }

        $screenshots = $this->screenshots;

        foreach ($this->newScreenshots as $file) {
            $screenshots[] = $images->store($file);
        }

        $work->fill([
            'title' => $this->title,
            'slug' => $this->slug,
            'client' => $this->client ?: null,
            'category' => $this->category ?: null,
            'summary' => $this->summary ?: null,
            'headline' => $this->headline ?: null,
            'description' => $this->description ?: null,
            'highlights' => $this->lines($this->highlights),
            'technologies' => array_values(array_filter(array_map('trim', explode(',', $this->technologies)))),
            'results' => array_values(array_filter(
                array_map(fn (array $result): array => ['value' => trim($result['value'] ?? ''), 'label' => trim($result['label'] ?? '')], $this->results),
                fn (array $result): bool => $result['value'] !== '',
            )),
            'live_url' => $this->live_url ?: null,
            'seo_description' => $this->seo_description ?: null,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'cover_path' => $coverPath,
            'screenshots' => $screenshots,
        ])->save();

        Storage::disk('public')->delete(array_values(array_diff($oldPaths, $work->imagePaths())));

        Flux::toast(variant: 'success', text: __('Work saved.'));

        $this->redirectRoute('admin.works.index', navigate: true);
    }

    /**
     * Split text into trimmed, non-empty lines.
     *
     * @return list<string>
     */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('admin.works.index')" wire:navigate class="text-sm">← {{ __('All works') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ $work ? __('Edit work') : __('Add a work') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Images are resized and compressed to WebP automatically, so phone photos and big screenshots are fine.') }}</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-8">
        <flux:fieldset>
            <flux:legend>{{ __('About the project') }}</flux:legend>
            <div class="grid gap-5">
                <flux:input wire:model.live.debounce.400ms="title" :label="__('Title')" :placeholder="__('e.g. Eze Catering website')" required />
                <flux:input wire:model.blur="slug" :label="__('Web address')" :description="url('work').'/'.($slug ?: '…')" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input wire:model="client" :label="__('Client')" :placeholder="__('e.g. Eze Catering Services')" />
                    <flux:input wire:model="category" :label="__('Category')" :placeholder="__('e.g. Business website')" list="work-categories" />
                    <datalist id="work-categories">
                        <option value="Business website"></option>
                        <option value="Portfolio website"></option>
                        <option value="E-commerce website"></option>
                        <option value="Blog & news website"></option>
                        <option value="Landing page"></option>
                        <option value="Custom web app"></option>
                    </datalist>
                </div>
                <flux:textarea wire:model="summary" :label="__('Short summary')" :description="__('One or two sentences shown on cards.')" rows="2" />
                <flux:input wire:model="headline" :label="__('Headline')" :placeholder="__('e.g. From slow and hard to find, to fast and fully indexed')" />
                <flux:textarea wire:model="description" :label="__('Description')" :description="__('What the client needed and what you did. Leave a blank line between paragraphs.')" rows="6" />
                <flux:textarea wire:model="highlights" :label="__('What we built')" :description="__('One feature per line. Shown with ticks.')" rows="4" />
                <flux:input wire:model="technologies" :label="__('Technologies')" :description="__('Separate with commas, e.g. Laravel, MySQL, Tailwind CSS')" />
                <flux:input wire:model="live_url" type="url" :label="__('Live website link')" placeholder="https://" />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Results') }}</flux:legend>
            <flux:text class="mb-3">{{ __('Numbers that show the difference you made, e.g. "59 → 92" + "mobile speed score".') }}</flux:text>
            <div class="grid gap-3">
                @foreach ($results as $index => $result)
                    <div class="flex items-start gap-2" wire:key="result-{{ $index }}">
                        <flux:input wire:model="results.{{ $index }}.value" :placeholder="__('Value')" class="max-w-40" :aria-label="__('Result value')" />
                        <flux:input wire:model="results.{{ $index }}.label" :placeholder="__('What it means')" :aria-label="__('Result label')" />
                        <flux:button icon="x-mark" variant="ghost" wire:click="removeResult({{ $index }})" :aria-label="__('Remove result')" />
                    </div>
                @endforeach
                @if (count($results) < 6)
                    <div><flux:button size="sm" icon="plus" wire:click="addResult">{{ __('Add a result') }}</flux:button></div>
                @endif
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Images') }}</flux:legend>
            <div class="grid gap-6">
                <div class="grid gap-3">
                    <flux:input type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp" :label="__('Cover image')" :description="__('A screenshot of the home page works best. Shown on cards and at the top of the project page.')" />
                    <div wire:loading wire:target="cover"><flux:text>{{ __('Uploading…') }}</flux:text></div>
                    @if ($cover && $cover->isPreviewable() && ! $errors->has('cover'))
                        <img src="{{ $cover->temporaryUrl() }}" alt="" class="aspect-[16/10] w-64 rounded-lg border border-zinc-200 object-cover object-top dark:border-zinc-700">
                    @elseif ($work?->cover_path)
                        <img src="{{ $work->coverThumbUrl() }}" alt="" class="aspect-[16/10] w-64 rounded-lg border border-zinc-200 object-cover object-top dark:border-zinc-700">
                    @endif
                </div>

                <div class="grid gap-3">
                    @if (count($screenshots) + count($newScreenshots) < \App\Models\Work::MAX_SCREENSHOTS)
                        <flux:input type="file" wire:model="newScreenshots" multiple accept="image/jpeg,image/png,image/webp" :label="__('Screenshots (up to :max)', ['max' => \App\Models\Work::MAX_SCREENSHOTS])" :description="__('Pages, phone views and the admin panel. Visitors can tap to enlarge.')" />
                    @else
                        <flux:text>{{ __('This work has the most screenshots allowed (:max). Remove one to add another.', ['max' => \App\Models\Work::MAX_SCREENSHOTS]) }}</flux:text>
                    @endif
                    <div wire:loading wire:target="newScreenshots"><flux:text>{{ __('Uploading…') }}</flux:text></div>
                    @error('newScreenshots.*') <flux:text class="text-red-600! dark:text-red-400!">{{ $message }}</flux:text> @enderror

                    @if (count($screenshots) || count($newScreenshots))
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($screenshots as $index => $path)
                                <div class="relative" wire:key="saved-{{ $path }}">
                                    <img src="{{ Storage::disk('public')->url(\App\Models\Work::thumbPath($path)) }}" alt="" class="aspect-[4/3] w-full rounded-lg border border-zinc-200 object-cover object-top dark:border-zinc-700">
                                    <flux:button size="xs" variant="danger" icon="x-mark" class="absolute end-2 top-2" wire:click="removeScreenshot({{ $index }})" :aria-label="__('Remove screenshot')" />
                                </div>
                            @endforeach
                            @foreach ($newScreenshots as $index => $file)
                                <div class="relative" wire:key="new-{{ $index }}-{{ $file->getFilename() }}">
                                    @if ($file->isPreviewable())
                                        <img src="{{ $file->temporaryUrl() }}" alt="" class="aspect-[4/3] w-full rounded-lg border border-amber-400 object-cover object-top">
                                    @endif
                                    <flux:badge size="sm" color="amber" class="absolute start-2 top-2">{{ __('New') }}</flux:badge>
                                    <flux:button size="xs" variant="danger" icon="x-mark" class="absolute end-2 top-2" wire:click="removeNewScreenshot({{ $index }})" :aria-label="__('Remove screenshot')" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Showing it on the website') }}</flux:legend>
            <div class="grid gap-5">
                <flux:switch wire:model="is_published" :label="__('Published')" :description="__('Turn off to keep it as a draft that visitors cannot see.')" />
                <flux:switch wire:model="is_featured" :label="__('Show on the home page')" :description="__('Featured works appear in the “Selected work” section. The first one is shown large.')" />
                <flux:input wire:model="sort_order" type="number" min="0" :label="__('Sort order')" :description="__('Lower numbers show first.')" class="max-w-40" />
                <flux:textarea wire:model="seo_description" :label="__('Google description')" :description="__('About 150 characters shown in Google results. Leave empty to use the summary.')" rows="2" />
            </div>
        </flux:fieldset>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit" data-test="save-work">
                <span wire:loading.remove wire:target="save">{{ __('Save work') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving and compressing images…') }}</span>
            </flux:button>
            <flux:button :href="route('admin.works.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
