<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Cost calculator')] class extends Component {
    /** @var array{calc_eyebrow: string, calc_title: string, calc_text: string, calc_extra_page_price: string} */
    public array $texts = ['calc_eyebrow' => '', 'calc_title' => '', 'calc_text' => '', 'calc_extra_page_price' => ''];

    /** @var list<array{name: string, price: string, pages: string}> */
    public array $types = [];

    /** @var list<array{name: string, price: string}> */
    public array $extras = [];

    /**
     * Load the saved prices.
     */
    public function mount(): void
    {
        $this->texts = array_intersect_key(Setting::values(), $this->texts);
        $this->types = array_map(fn (array $row): array => array_map('strval', $row), Setting::list('calc_site_types'));
        $this->extras = array_map(fn (array $row): array => array_map('strval', $row), Setting::list('calc_extras'));
    }

    /**
     * Add an empty row to a list.
     */
    public function addRow(string $list): void
    {
        match ($list) {
            'types' => count($this->types) < 8 ? $this->types[] = ['name' => '', 'price' => '', 'pages' => '1'] : null,
            'extras' => count($this->extras) < 12 ? $this->extras[] = ['name' => '', 'price' => ''] : null,
            default => abort(404),
        };
    }

    /**
     * Remove a row from a list.
     */
    public function removeRow(string $list, int $index): void
    {
        abort_unless(in_array($list, ['types', 'extras'], true), 404);

        unset($this->{$list}[$index]);
        $this->{$list} = array_values($this->{$list});
    }

    /**
     * Save the calculator.
     */
    public function save(): void
    {
        $clean = fn (string $value): string => str_replace([',', '₦', ' '], '', $value);

        $this->texts['calc_extra_page_price'] = $clean($this->texts['calc_extra_page_price']);
        $this->types = array_map(fn (array $row): array => [...$row, 'price' => $clean($row['price'])], $this->types);
        $this->extras = array_map(fn (array $row): array => [...$row, 'price' => $clean($row['price'])], $this->extras);

        $this->validate([
            'texts.calc_eyebrow' => ['nullable', 'string', 'max:60'],
            'texts.calc_title' => ['required', 'string', 'max:120'],
            'texts.calc_text' => ['nullable', 'string', 'max:300'],
            'texts.calc_extra_page_price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'types' => ['array', 'min:1', 'max:8'],
            'types.*.name' => ['required', 'string', 'max:80'],
            'types.*.price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'types.*.pages' => ['required', 'integer', 'min:1', 'max:100'],
            'extras' => ['array', 'max:12'],
            'extras.*.name' => ['required', 'string', 'max:80'],
            'extras.*.price' => ['required', 'integer', 'min:0', 'max:100000000'],
        ], [
            'types.min' => 'Add at least one type of website.',
            '*.*.price.integer' => 'Use a number, e.g. 150000.',
            'texts.calc_extra_page_price.integer' => 'Use a number, e.g. 10000.',
        ]);

        Setting::put([
            ...array_map(fn (?string $value): string => trim((string) $value), $this->texts),
            'calc_site_types' => array_map(fn (array $row): array => ['name' => trim($row['name']), 'price' => $row['price'], 'pages' => $row['pages']], $this->types),
            'calc_extras' => array_map(fn (array $row): array => ['name' => trim($row['name']), 'price' => $row['price']], $this->extras),
        ]);

        Flux::toast(variant: 'success', text: __('Cost calculator saved.'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Cost calculator') }}</flux:heading>
            <flux:text class="mt-1">{{ __('The price estimate on your home page. Visitors pick a type, pages and extras, then send the estimate with the quote form.') }}</flux:text>
        </div>
        <flux:button icon="arrow-top-right-on-square" :href="route('home').'#estimate'" target="_blank">{{ __('View on website') }}</flux:button>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="grid gap-5">
            <flux:heading size="lg">{{ __('Heading and text') }}</flux:heading>
            <flux:input wire:model="texts.calc_eyebrow" :label="__('Small label')" />
            <flux:input wire:model="texts.calc_title" :label="__('Heading')" required />
            <flux:textarea wire:model="texts.calc_text" :label="__('Text')" rows="2" />
        </flux:card>

        <flux:card class="grid gap-4">
            <div>
                <flux:heading size="lg">{{ __('Types of website') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Use the same names as your services so the quote form picks the right one.') }}</flux:text>
            </div>
            @foreach ($types as $index => $row)
                <div class="grid gap-2 sm:grid-cols-[1fr_9rem_6rem_auto] sm:items-start" wire:key="type-{{ $index }}">
                    <flux:input wire:model="types.{{ $index }}.name" :placeholder="__('Name')" :aria-label="__('Name')" />
                    <flux:input wire:model="types.{{ $index }}.price" :placeholder="__('Price (₦)')" inputmode="numeric" :aria-label="__('Starting price in naira')" />
                    <flux:input wire:model="types.{{ $index }}.pages" type="number" min="1" :aria-label="__('Pages included')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeRow('types', {{ $index }})" :aria-label="__('Remove')" />
                </div>
            @endforeach
            <flux:text class="text-sm">{{ __('Columns: name, starting price in ₦, pages included.') }}</flux:text>
            <div><flux:button size="sm" icon="plus" wire:click="addRow('types')">{{ __('Add a type') }}</flux:button></div>
            <flux:input wire:model="texts.calc_extra_page_price" :label="__('Price of each extra page (₦)')" inputmode="numeric" class="max-w-56" required />
        </flux:card>

        <flux:card class="grid gap-4">
            <flux:heading size="lg">{{ __('Extras') }}</flux:heading>
            @foreach ($extras as $index => $row)
                <div class="flex items-start gap-2" wire:key="extra-{{ $index }}">
                    <flux:input wire:model="extras.{{ $index }}.name" :placeholder="__('Extra, e.g. Logo design')" :aria-label="__('Name')" />
                    <flux:input wire:model="extras.{{ $index }}.price" :placeholder="__('Price (₦)')" inputmode="numeric" class="max-w-36" :aria-label="__('Price in naira')" />
                    <flux:button icon="x-mark" variant="ghost" wire:click="removeRow('extras', {{ $index }})" :aria-label="__('Remove')" />
                </div>
            @endforeach
            <div><flux:button size="sm" icon="plus" wire:click="addRow('extras')">{{ __('Add an extra') }}</flux:button></div>
        </flux:card>

        <div class="sticky bottom-4 z-10">
            <flux:button variant="primary" type="submit" class="shadow-lg">{{ __('Save calculator') }}</flux:button>
        </div>
    </form>
</div>
