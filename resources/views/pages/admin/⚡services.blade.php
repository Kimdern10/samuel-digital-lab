<?php

use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services & prices')] class extends Component {
    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $features = '';

    public string $price = '';

    public string $price_note = '';

    public string $badge = '';

    public bool $is_visible = true;

    public int $sort_order = 0;

    /**
     * Every service, in the order shown on the home page.
     *
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::query()->ordered()->get();
    }

    /**
     * Open the form for a new service.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->sort_order = (int) Service::query()->max('sort_order') + 1;

        Flux::modal('service-form')->show();
    }

    /**
     * Open the form filled with an existing service.
     */
    public function edit(Service $service): void
    {
        $this->resetForm();

        $this->editingId = $service->id;
        $this->title = $service->title;
        $this->description = $service->description;
        $this->features = implode("\n", $service->features ?? []);
        $this->price = $service->price === null ? '' : (string) $service->price;
        $this->price_note = (string) $service->price_note;
        $this->badge = (string) $service->badge;
        $this->is_visible = $service->is_visible;
        $this->sort_order = $service->sort_order;

        Flux::modal('service-form')->show();
    }

    /**
     * Save the service shown in the form.
     */
    public function save(): void
    {
        $this->price = str_replace([',', '₦', ' '], '', $this->price);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'features' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'price_note' => ['nullable', 'string', 'max:60'],
            'badge' => ['nullable', 'string', 'max:30'],
            'is_visible' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'price.integer' => 'Enter the price as a number, e.g. 150000. Leave empty for "On quote".',
        ]);

        $attributes = [
            ...$validated,
            'features' => array_values(array_filter(array_map('trim', preg_split('/\R/', $this->features) ?: []))),
            'price' => $this->price === '' ? null : (int) $this->price,
            'price_note' => $this->price_note ?: null,
            'badge' => $this->badge ?: null,
        ];

        if ($this->editingId) {
            Service::query()->findOrFail($this->editingId)->update($attributes);
        } else {
            Service::query()->create($attributes);
        }

        Flux::modal('service-form')->close();
        Flux::toast(variant: 'success', text: __('Service saved.'));

        $this->resetForm();
        unset($this->services);
    }

    /**
     * Delete a service.
     */
    public function delete(Service $service): void
    {
        $service->delete();

        Flux::toast(variant: 'success', text: __('Service deleted.'));
        unset($this->services);
    }

    /**
     * Clear the form.
     */
    private function resetForm(): void
    {
        $this->reset('editingId', 'title', 'description', 'features', 'price', 'price_note', 'badge', 'is_visible', 'sort_order');
        $this->resetValidation();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Services & prices') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The service cards on your home page. Leave the price empty to show “On quote”.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add a service') }}</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->services as $service)
            <flux:card wire:key="service-{{ $service->id }}" class="flex flex-col gap-3 {{ $service->is_visible ? '' : 'opacity-60' }}">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($service->badge)
                        <flux:badge size="sm" color="green">{{ $service->badge }}</flux:badge>
                    @endif
                    @unless ($service->is_visible)
                        <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge>
                    @endunless
                </div>
                <flux:heading size="lg">{{ $service->title }}</flux:heading>
                <flux:text>{{ $service->description }}</flux:text>
                <div class="mt-auto flex items-baseline justify-between gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <span class="font-mono text-xl font-bold">{{ $service->formattedPrice() }}</span>
                    <span class="text-sm text-zinc-500">{{ $service->price_note }}</span>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $service->id }})">{{ __('Edit') }}</flux:button>
                    <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $service->id }})" wire:confirm="{{ __('Delete this service?') }}" :aria-label="__('Delete')" />
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="service-form" class="w-full max-w-lg">
        <form wire:submit="save" class="grid gap-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit service') : __('Add a service') }}</flux:heading>
            <flux:input wire:model="title" :label="__('Title')" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" required />
            <flux:textarea wire:model="features" :label="__('What is included')" :description="__('One item per line.')" rows="3" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="price" :label="__('Price (₦)')" inputmode="numeric" placeholder="150000" :description="__('Empty = On quote')" />
                <flux:input wire:model="price_note" :label="__('Price note')" placeholder="from · 7 days" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="badge" :label="__('Badge')" placeholder="Most requested" :description="__('Highlights the card.')" />
                <flux:input wire:model="sort_order" type="number" min="0" :label="__('Sort order')" />
            </div>
            <flux:switch wire:model="is_visible" :label="__('Show on the home page')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
