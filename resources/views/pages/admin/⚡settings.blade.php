<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Business details')] class extends Component {
    /** @var array<string, string> */
    public array $settings = [];

    /**
     * Load the saved settings.
     */
    public function mount(): void
    {
        $this->settings = Setting::values();
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
