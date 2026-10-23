<?php

use App\Models\Faq;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Questions')] class extends Component {
    public ?int $editingId = null;

    public string $question = '';

    public string $answer = '';

    public bool $is_visible = true;

    public int $sort_order = 0;

    /**
     * Every question, in the order shown on the site.
     *
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->ordered()->get();
    }

    /**
     * Open the form for a new question.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->sort_order = (int) Faq::query()->max('sort_order') + 1;

        Flux::modal('faq-form')->show();
    }

    /**
     * Open the form filled with an existing question.
     */
    public function edit(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->is_visible = $faq->is_visible;
        $this->sort_order = $faq->sort_order;

        Flux::modal('faq-form')->show();
    }

    /**
     * Save the question shown in the form.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:2000'],
            'is_visible' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        Faq::query()->updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('faq-form')->close();
        Flux::toast(variant: 'success', text: __('Question saved.'));

        $this->resetForm();
        unset($this->faqs);
    }

    /**
     * Delete a question.
     */
    public function delete(int $id): void
    {
        Faq::query()->findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Question deleted.'));
        unset($this->faqs);
    }

    /**
     * Clear the form.
     */
    private function resetForm(): void
    {
        $this->reset('editingId', 'question', 'answer', 'is_visible', 'sort_order');
        $this->resetValidation();
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Questions') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Questions and answers shown near the bottom of the home page. They also help you show up on Google.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add a question') }}</flux:button>
    </div>

    @if ($this->faqs->isEmpty())
        <flux:callout icon="question-mark-circle">{{ __('No questions yet. The section is hidden until you add one.') }}</flux:callout>
    @else
        <div class="grid gap-3">
            @foreach ($this->faqs as $faq)
                <div wire:key="faq-{{ $faq->id }}" class="flex flex-wrap items-start gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 {{ $faq->is_visible ? '' : 'opacity-60' }}">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium">{{ $faq->question }}</div>
                        <flux:text class="mt-1 line-clamp-2">{{ $faq->answer }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @unless ($faq->is_visible)
                            <flux:badge size="sm" color="zinc">{{ __('Hidden') }}</flux:badge>
                        @endunless
                        <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $faq->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $faq->id }})" wire:confirm="{{ __('Delete this question?') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="faq-form" class="w-full max-w-lg">
        <form wire:submit="save" class="grid gap-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit question') : __('Add a question') }}</flux:heading>
            <flux:input wire:model="question" :label="__('Question')" required />
            <flux:textarea wire:model="answer" :label="__('Answer')" rows="5" required />
            <flux:input wire:model="sort_order" type="number" min="0" :label="__('Sort order')" class="max-w-40" />
            <flux:switch wire:model="is_visible" :label="__('Show on the website')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
