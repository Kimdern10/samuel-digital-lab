<?php

use App\Models\Setting;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Business details')] class extends Component {
    use WithFileUploads;

    /** @var array<string, string> */
    public array $settings = [];

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public bool $showNameWithLogo = true;

    /**
     * Save a newly chosen logo straight away.
     */
    public function updatedLogo(ImageOptimizer $images): void
    {
        $this->validate(['logo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']], [
            'logo.image' => 'The logo must be a picture (PNG, JPG or WebP).',
            'logo.mimes' => 'The logo must be a PNG, JPG or WebP file. A PNG with a transparent background works best.',
        ]);

        $oldPath = Setting::get('logo_path');

        Setting::put(['logo_path' => $images->storeLogo($this->logo)]);

        if ($oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('logo');
        $this->settings = Setting::values();

        Flux::toast(variant: 'success', text: __('Logo saved. It now shows on your website and admin.'));
    }

    /**
     * Go back to the "SD" mark.
     */
    public function removeLogo(): void
    {
        $oldPath = Setting::get('logo_path');

        if ($oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }

        Setting::put(['logo_path' => '']);
        $this->settings = Setting::values();

        Flux::toast(variant: 'success', text: __('Logo removed.'));
    }

    /**
     * Choose whether the business name shows beside the logo.
     */
    public function updatedShowNameWithLogo(): void
    {
        Setting::put(['logo_show_name' => $this->showNameWithLogo ? '1' : '0']);
    }

    /**
     * Load the saved settings.
     */
    public function mount(): void
    {
        $this->settings = Setting::values();
        $this->showNameWithLogo = $this->settings['logo_show_name'] === '1';
    }

    /**
     * Save the settings.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'settings.business_name' => ['required', 'string', 'max:100'],
            'settings.tagline' => ['nullable', 'string', 'max:150'],
            'settings.whatsapp_primary' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-()]{6,18}$/'],
            'settings.whatsapp_secondary' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9\s\-()]{6,18}$/'],
            'settings.email' => ['required', 'email', 'max:150'],
            'settings.city' => ['required', 'string', 'max:100'],
            'settings.facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'settings.instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'settings.x_url' => ['nullable', 'url:http,https', 'max:255'],
            'settings.linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
            'settings.tiktok_url' => ['nullable', 'url:http,https', 'max:255'],
        ], attributes: [
            'settings.business_name' => 'business name',
            'settings.whatsapp_primary' => 'main WhatsApp number',
            'settings.whatsapp_secondary' => 'second WhatsApp number',
            'settings.email' => 'email',
            'settings.city' => 'city',
        ])['settings'];

        Setting::put(array_intersect_key($validated, Setting::DEFAULTS));

        Flux::toast(variant: 'success', text: __('Business details saved.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Business details') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Shown in the contact section, footer and WhatsApp button. Quote requests are emailed to the address below.') }}</flux:text>
    </div>

    <flux:card class="grid gap-5">
        <div>
            <flux:heading size="lg">{{ __('Logo') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Shown at the top and bottom of your website, in the admin and on the browser tab. A PNG with a transparent background works best.') }}</flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-5">
            <div class="flex h-20 min-w-20 items-center justify-center rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700">
                @if ($logoUrl = \App\Models\Setting::logoUrl())
                    <img src="{{ $logoUrl }}" alt="{{ __('Current logo') }}" class="h-14 w-auto max-w-56 object-contain" data-test="current-logo">
                @else
                    <img src="{{ asset('brand/logo-icon.svg') }}" alt="{{ __('Built-in logo') }}" class="size-12">
                @endif
            </div>
            <div class="grid gap-2">
                <flux:input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" :aria-label="__('Upload a logo')" />
                <div wire:loading wire:target="logo"><flux:text>{{ __('Uploading…') }}</flux:text></div>
                @error('logo') <flux:text class="text-red-600! dark:text-red-400!">{{ $message }}</flux:text> @enderror
                @if (\App\Models\Setting::logoUrl())
                    <div><flux:button size="sm" variant="ghost" icon="trash" wire:click="removeLogo" wire:confirm="{{ __('Remove the logo and go back to the SD mark?') }}">{{ __('Remove logo') }}</flux:button></div>
                @endif
            </div>
        </div>

        <div class="grid gap-2">
            <flux:text>{{ __('Your logo files, for WhatsApp, flyers and social media:') }}</flux:text>
            <div class="flex flex-wrap gap-2">
                @foreach (['logo-full.png' => __('Full logo'), 'logo-full-white.png' => __('White, for dark backgrounds'), 'logo-stacked.png' => __('Stacked'), 'logo-icon-512.png' => __('Icon (profile picture)'), 'logo-icon.svg' => __('Icon (SVG)')] as $file => $label)
                    <flux:button size="sm" icon="arrow-down-tray" :href="asset('brand/'.$file)" download>{{ $label }}</flux:button>
                @endforeach
            </div>
        </div>

        <flux:switch wire:model.live="showNameWithLogo" :label="__('Show the business name beside the logo')" :description="__('Turn off if your logo already contains the name.')" />
    </flux:card>

    <form wire:submit="save" class="flex flex-col gap-8">
        <flux:fieldset>
            <flux:legend>{{ __('Business') }}</flux:legend>
            <div class="grid gap-5">
                <flux:input wire:model="settings.business_name" :label="__('Business name')" required />
                <flux:input wire:model="settings.tagline" :label="__('Footer tagline')" />
                <flux:input wire:model="settings.city" :label="__('City and state')" :description="__('Shown in the footer and to Google, e.g. Port Harcourt, Rivers State')" required />
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Contact') }}</flux:legend>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="settings.whatsapp_primary" :label="__('Main WhatsApp number')" inputmode="tel" required />
                <flux:input wire:model="settings.whatsapp_secondary" :label="__('Second WhatsApp number')" inputmode="tel" />
                <div class="sm:col-span-2">
                    <flux:input wire:model="settings.email" type="email" :label="__('Email')" :description="__('Quote requests are sent here.')" required />
                </div>
            </div>
        </flux:fieldset>

        <flux:fieldset>
            <flux:legend>{{ __('Social media') }}</flux:legend>
            <flux:text class="mb-3">{{ __('Paste the full link to your page. Leave empty to hide it.') }}</flux:text>
            <div class="grid gap-5">
                <flux:input wire:model="settings.facebook_url" type="url" label="Facebook" placeholder="https://facebook.com/..." />
                <flux:input wire:model="settings.instagram_url" type="url" label="Instagram" placeholder="https://instagram.com/..." />
                <flux:input wire:model="settings.x_url" type="url" label="X (Twitter)" placeholder="https://x.com/..." />
                <flux:input wire:model="settings.linkedin_url" type="url" label="LinkedIn" placeholder="https://linkedin.com/..." />
                <flux:input wire:model="settings.tiktok_url" type="url" label="TikTok" placeholder="https://tiktok.com/@..." />
            </div>
        </flux:fieldset>

        <div>
            <flux:button variant="primary" type="submit">{{ __('Save details') }}</flux:button>
        </div>
    </form>
</div>
