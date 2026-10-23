<?php

use App\Models\Testimonial;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Testimonials')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $quote = '';

    public int $rating = 5;

    public bool $is_visible = true;

    public int $sort_order = 0;

    /**
     * Every testimonial, in the order shown on the site.
     *
     * @return Collection<int, Testimonial>
     */
    #[Computed]
    public function testimonials(): Collection
    {
        return Testimonial::query()->ordered()->get();
    }

    /**
     * Open the form for a new testimonial.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->sort_order = (int) Testimonial::query()->max('sort_order') + 1;

        Flux::modal('testimonial-form')->show();
    }

    /**
     * Open the form filled with an existing testimonial.
     */
    public function edit(int $id): void
    {
        $testimonial = Testimonial::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $testimonial->id;
        $this->name = $testimonial->name;
        $this->role = (string) $testimonial->role;
        $this->quote = $testimonial->quote;
        $this->rating = $testimonial->rating;
        $this->is_visible = $testimonial->is_visible;
        $this->sort_order = $testimonial->sort_order;

        Flux::modal('testimonial-form')->show();
    }

    /**
     * Save the testimonial shown in the form.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:800'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'is_visible' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['role'] = $validated['role'] ?: null;

        Testimonial::query()->updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('testimonial-form')->close();
        Flux::toast(variant: 'success', text: __('Testimonial saved.'));

        $this->resetForm();
        unset($this->testimonials);
    }

    /**
     * Delete a testimonial.
     */
    public function delete(int $id): void
    {
        Testimonial::query()->findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Testimonial deleted.'));
        unset($this->testimonials);
    }

    /**
     * Clear the form.
     */
    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'role', 'quote', 'rating', 'is_visible', 'sort_order');
        $this->resetValidation();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Testimonials') }}</flux:heading>
            <flux:text class="mt-1">{{ __('What clients say about you. The section shows on the home page once you add one.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add a testimonial') }}</flux:button>
    </div>

    @if ($this->testimonials->isEmpty())
        <flux:callout icon="chat-bubble-bottom-center-text">{{ __('No testimonials yet. Ask a happy client for a sentence or two and add it here.') }}</flux:callout>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->testimonials as $testimonial)
                <flux:card wire:key="testimonial-{{ $testimonial->id }}" class="flex flex-col gap-3 {{ $testimonial->is_visible ? '' : 'opacity-60' }}">
                    <div class="text-amber-500">{{ str_repeat('★', $testimonial->rating) }}<span class="text-zinc-300 dark:text-zinc-600">{{ str_repeat('★', 5 - $testimonial->rating) }}</span></div>
                    <flux:text class="flex-1">“{{ $testimonial->quote }}”</flux:text>
                    <div>
                        <div class="font-medium">{{ $testimonial->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $testimonial->role }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @unless ($testimonial->is_visible)
                            <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge>
                        @endunless
                        <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $testimonial->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $testimonial->id }})" wire:confirm="{{ __('Delete this testimonial?') }}" :aria-label="__('Delete')" />
                    </div>
                </flux:card>
            @endforeach
        </div>
    @endif

    <flux:modal name="testimonial-form" class="w-full max-w-lg">
        <form wire:submit="save" class="grid gap-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit testimonial') : __('Add a testimonial') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Client name')" required />
            <flux:input wire:model="role" :label="__('Role and business')" placeholder="Owner, Eze Catering Services" />
            <flux:textarea wire:model="quote" :label="__('What they said')" rows="4" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select wire:model="rating" :label="__('Stars')">
                    @foreach ([5, 4, 3, 2, 1] as $stars)
                        <flux:select.option :value="$stars">{{ str_repeat('★', $stars) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="sort_order" type="number" min="0" :label="__('Sort order')" />
            </div>
            <flux:switch wire:model="is_visible" :label="__('Show on the website')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
