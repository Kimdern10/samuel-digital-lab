<?php

use App\Models\Setting;
use App\Models\Work;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('About me')] class extends Component {
    use WithFileUploads;

    /** @var array<string, string> */
    public array $about = [];

    /** @var list<array{value: string, label: string}> */
    public array $facts = [];

    public string $skills = '';

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    /** @var TemporaryUploadedFile|null */
    public $cv = null;

    /**
     * Load the saved About details.
     */
    public function mount(): void
    {
        $this->about = array_intersect_key(Setting::values(), array_flip(['about_eyebrow', 'about_title', 'about_name', 'about_role', 'about_story', 'github_url']));
        $this->facts = Setting::list('about_facts');
        $this->skills = implode("\n", Setting::list('about_skills'));
    }

    /**
     * Add an empty fact row.
     */
    public function addFact(): void
    {
        if (count($this->facts) < 4) {
            $this->facts[] = ['value' => '', 'label' => ''];
        }
    }

    /**
     * Remove a fact row.
     */
    public function removeFact(int $index): void
    {
        unset($this->facts[$index]);
        $this->facts = array_values($this->facts);
    }

    /**
     * Save a newly chosen photo straight away.
     */
    public function updatedPhoto(ImageOptimizer $images): void
    {
        $this->validate(['photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240']], [
            'photo.image' => 'Your photo must be a picture (JPG, PNG or WebP).',
            'photo.mimes' => 'Your photo must be a JPG, PNG or WebP file.',
        ]);

        $this->replaceFile('about_photo_path', $images->store($this->photo, 'branding'));
        $this->reset('photo');

        Flux::toast(variant: 'success', text: __('Photo saved.'));
    }

    /**
     * Save a newly chosen CV straight away.
     */
    public function updatedCv(): void
    {
        $this->validate(['cv' => ['file', 'mimes:pdf', 'max:5120']], [
            'cv.mimes' => 'Your CV must be a PDF file.',
            'cv.max' => 'Your CV must be smaller than 5 MB.',
        ]);

        $name = Str::slug(Setting::get('about_name') ?: 'cv').'-cv-'.Str::lower(Str::random(6)).'.pdf';
        $this->replaceFile('about_cv_path', $this->cv->storeAs('branding', $name, 'public'));
        $this->reset('cv');

        Flux::toast(variant: 'success', text: __('CV saved. Visitors can download it from your About page.'));
    }

    /**
     * Remove the photo or the CV.
     */
    public function removeFile(string $key): void
    {
        abort_unless(in_array($key, ['about_photo_path', 'about_cv_path'], true), 404);

        $this->replaceFile($key, '');

        Flux::toast(variant: 'success', text: $key === 'about_photo_path' ? __('Photo removed.') : __('CV removed.'));
    }

    /**
     * Save the text, facts and skills.
     */
    public function save(): void
    {
        $this->validate([
            'about.about_eyebrow' => ['nullable', 'string', 'max:60'],
            'about.about_title' => ['required', 'string', 'max:120'],
            'about.about_name' => ['required', 'string', 'max:80'],
            'about.about_role' => ['nullable', 'string', 'max:100'],
            'about.about_story' => ['required', 'string', 'max:5000'],
            'about.github_url' => ['nullable', 'url:http,https', 'max:255'],
            'facts' => ['array', 'max:4'],
            'facts.*.value' => ['required', 'string', 'max:20'],
            'facts.*.label' => ['required', 'string', 'max:60'],
            'skills' => ['nullable', 'string', 'max:1000'],
        ], [
            'about.about_name.required' => 'Your name is needed.',
            'about.about_story.required' => 'Write a few lines about yourself.',
            'facts.*.value.required' => 'Add the number, or remove this fact.',
            'facts.*.label.required' => 'Say what the number means, or remove this fact.',
        ], [
            'about.about_title' => 'heading',
            'about.github_url' => 'GitHub link',
        ]);

        Setting::put([
            ...array_map(fn (?string $value): string => trim((string) $value), $this->about),
            'about_facts' => array_map(fn (array $fact): array => ['value' => trim($fact['value']), 'label' => trim($fact['label'])], $this->facts),
            'about_skills' => array_values(array_filter(array_map('trim', preg_split('/\R/', $this->skills) ?: []))),
        ]);

        Flux::toast(variant: 'success', text: __('About me saved.'));
    }

    /**
     * Swap a stored file for another (or none), deleting the old one.
     */
    private function replaceFile(string $key, string $newPath): void
    {
        $oldPath = Setting::get($key);

        Setting::put([$key => $newPath]);

        if ($oldPath !== '' && $oldPath !== $newPath) {
            Storage::disk('public')->delete(array_unique([$oldPath, Work::thumbPath($oldPath)]));
        }
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('About me') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Clients like to know who they are hiring. This shows on your home page and on its own About page.') }}</flux:text>
        </div>
        <flux:button icon="arrow-top-right-on-square" :href="route('about')" target="_blank">{{ __('View About page') }}</flux:button>
    </div>

    <flux:card class="grid gap-4">
        <div>
            <flux:heading size="lg">{{ __('Your photo') }}</flux:heading>
            <flux:text class="mt-1">{{ __('A clear, friendly photo of your face, in good light. A phone photo is fine; portrait (tall) works best.') }}</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-5">
            @if ($photoUrl = Setting::fileUrl('about_photo_path'))
                <div class="relative">
                    <img src="{{ Storage::disk('public')->url(Work::thumbPath(Setting::get('about_photo_path'))) }}" alt="{{ __('Your photo') }}" class="aspect-[4/5] w-36 rounded-xl border border-zinc-200 object-cover object-top dark:border-zinc-700" data-test="about-photo">
                    <div class="absolute end-2 top-2">
                        <flux:button size="xs" variant="danger" icon="x-mark" wire:click="removeFile('about_photo_path')" wire:confirm="{{ __('Remove your photo?') }}" :aria-label="__('Remove photo')" />
                    </div>
                </div>
            @else
                <div class="flex aspect-[4/5] w-36 items-center justify-center rounded-xl border border-dashed border-zinc-300 text-sm text-zinc-500 dark:border-zinc-600">{{ __('No photo yet') }}</div>
            @endif
            <div class="grid gap-2">
                <flux:input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" :aria-label="__('Upload your photo')" />
                <div wire:loading wire:target="photo"><flux:text>{{ __('Uploading…') }}</flux:text></div>
                @error('photo') <flux:text class="text-red-600! dark:text-red-400!">{{ $message }}</flux:text> @enderror
            </div>
        </div>
    </flux:card>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Who you are') }}</flux:heading>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="about.about_name" :label="__('Your name')" required />
                <flux:input wire:model="about.about_role" :label="__('Your role')" placeholder="Founder & Lead Developer" />
                <flux:input wire:model="about.about_eyebrow" :label="__('Small label')" />
                <flux:input wire:model="about.about_title" :label="__('Heading')" required />
            </div>
            <flux:textarea wire:model="about.about_story" :label="__('Your story')" :description="__('3 to 5 short sentences or paragraphs. Leave an empty line between paragraphs. The home page shows the first two; the About page shows all of it.')" rows="10" required />
        </flux:card>

        <flux:card class="grid gap-5">
            <div>
                <flux:heading size="lg">{{ __('Quick facts') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Up to 4 true numbers, e.g. "4+" years building websites, "20+" websites launched. Leave empty to hide.') }}</flux:text>
            </div>
            @foreach ($facts as $index => $fact)
                <div class="flex items-start gap-2" wire:key="fact-{{ $index }}">
                    <flux:input wire:model="facts.{{ $index }}.value" placeholder="4+" class="max-w-28" :aria-label="__('Number')" />
                    <flux:input wire:model="facts.{{ $index }}.label" placeholder="years building websites" :aria-label="__('What it means')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeFact({{ $index }})" :aria-label="__('Remove fact')" />
                </div>
            @endforeach
            @if (count($facts) < 4)
                <div><flux:button size="sm" icon="plus" wire:click="addFact">{{ __('Add a fact') }}</flux:button></div>
            @endif
        </flux:card>

        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Skills and links') }}</flux:heading>
            <flux:textarea wire:model="skills" :label="__('Skills')" :description="__('One per line.')" rows="5" />
            <flux:input wire:model="about.github_url" type="url" :label="__('GitHub link (optional)')" placeholder="https://github.com/..." />
            <flux:text class="text-sm">{{ __('Your WhatsApp, email, LinkedIn and other social links come from') }} <flux:link :href="route('admin.settings')" wire:navigate>{{ __('Business details') }}</flux:link>.</flux:text>
        </flux:card>

        <div class="sticky bottom-4 z-10">
            <flux:button variant="primary" type="submit" class="shadow-lg">{{ __('Save About me') }}</flux:button>
        </div>
    </form>

    <flux:card class="grid gap-4">
        <div>
            <flux:heading size="lg">{{ __('CV (optional)') }}</flux:heading>
            <flux:text class="mt-1">{{ __('A PDF visitors can download from your About page.') }}</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($cvUrl = Setting::fileUrl('about_cv_path'))
                <flux:button size="sm" icon="document-text" :href="$cvUrl" target="_blank">{{ __('View current CV') }}</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeFile('about_cv_path')" wire:confirm="{{ __('Remove your CV?') }}">{{ __('Remove') }}</flux:button>
            @endif
            <flux:input type="file" wire:model="cv" accept="application/pdf" :aria-label="__('Upload your CV')" />
            <div wire:loading wire:target="cv"><flux:text>{{ __('Uploading…') }}</flux:text></div>
        </div>
        @error('cv') <flux:text class="text-red-600! dark:text-red-400!">{{ $message }}</flux:text> @enderror
    </flux:card>
</div>
