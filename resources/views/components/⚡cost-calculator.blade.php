<?php

use App\Support\CostEstimator;
use Livewire\Component;

new class extends Component {
    public int $type = 1;

    public int $pages = 6;

    /** @var list<int> */
    public array $extras = [];

    /**
     * Start with the business website and its included pages.
     */
    public function mount(): void
    {
        $types = CostEstimator::siteTypes();
        $this->type = isset($types[1]) ? 1 : 0;
        $this->pages = $types[$this->type]['pages'] ?? 1;
    }

    /**
     * Send the estimate to the quote form below so the visitor only adds their contact details.
     */
    public function useEstimate(): void
    {
        $this->validate([
            'type' => ['required', 'integer', 'min:0', 'max:'.max(0, count(CostEstimator::siteTypes()) - 1)],
            'pages' => ['required', 'integer', 'min:1', 'max:100'],
            'extras' => ['array'],
            'extras.*' => ['integer', 'min:0', 'max:50'],
        ]);

        $estimate = CostEstimator::estimate($this->type, $this->pages, array_map('intval', $this->extras));

        $lines = [
            "Estimate from the website cost calculator: {$estimate['type']}, {$estimate['pages']} pages",
        ];

        if ($estimate['extras'] !== []) {
            $lines[] = 'Extras: '.implode(', ', $estimate['extras']);
        }

        $lines[] = 'Estimated total: '.CostEstimator::naira($estimate['total']);

        $this->dispatch('estimate-ready',
            service: $estimate['type'],
            budget: CostEstimator::budgetFor($estimate['total']),
            details: implode("\n", $lines)."\n\nAbout my business: ",
        );
    }
}; ?>

@php
    $types = \App\Support\CostEstimator::siteTypes();
    $extraOptions = \App\Support\CostEstimator::extras();
    $pagePrice = \App\Support\CostEstimator::extraPagePrice();
@endphp

<div class="calc" x-data="{
        types: @js($types),
        extraOptions: @js($extraOptions),
        pagePrice: @js($pagePrice),
        get chosen() { return this.types[$wire.type] ?? this.types[0]; },
        get pages() { return Math.max(this.chosen.pages, Number($wire.pages) || 0); },
        get total() {
            const extras = ($wire.extras || []).reduce((sum, index) => sum + (this.extraOptions[index]?.price || 0), 0);
            return this.chosen.price + (this.pages - this.chosen.pages) * this.pagePrice + extras;
        },
        naira(value) { return '₦' + Math.round(value).toLocaleString('en-NG'); },
        pickType(index) { $wire.type = index; $wire.pages = this.types[index].pages; },
    }">
    <div class="calc__options">
        <fieldset class="calc__group">
            <legend>1. What kind of website?</legend>
            <div class="calc__types">
                @foreach ($types as $index => $option)
                    <button type="button" class="calc__type" wire:key="type-{{ $index }}" x-on:click="pickType({{ $index }})" :class="{ 'is-active': $wire.type == {{ $index }} }" :aria-pressed="($wire.type == {{ $index }}).toString()">
                        <b>{{ $option['name'] }}</b>
                        <span>from {{ \App\Support\CostEstimator::naira($option['price']) }} · {{ $option['pages'] }} {{ $option['pages'] === 1 ? 'page' : 'pages' }}</span>
                    </button>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="calc__group">
            <legend>2. How many pages? <output x-text="pages"></output></legend>
            <input type="range" wire:model="pages" :min="chosen.pages" max="40" step="1" aria-label="Number of pages">
            <p class="calc__hint">
                <span x-text="chosen.pages"></span> included<template x-if="pages > chosen.pages"><span>, plus <span x-text="pages - chosen.pages"></span> extra at <span x-text="naira(pagePrice)"></span> each</span></template>.
            </p>
        </fieldset>

        @if (count($extraOptions) > 0)
            <fieldset class="calc__group">
                <legend>3. Any extras?</legend>
                <div class="calc__extras">
                    @foreach ($extraOptions as $index => $extra)
                        <label class="calc__extra" wire:key="extra-{{ $index }}">
                            <input type="checkbox" wire:model="extras" value="{{ $index }}">
                            <span>{{ $extra['name'] }}</span>
                            <b>+{{ \App\Support\CostEstimator::naira($extra['price']) }}</b>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
    </div>

    <aside class="calc__total" aria-live="polite">
        <span class="calc__label">Estimated price</span>
        <b class="calc__amount" x-text="naira(total)">{{ \App\Support\CostEstimator::naira(\App\Support\CostEstimator::estimate($type, $pages, $extras)['total']) }}</b>
        <p>One-off price for design and build. Hosting and domain are paid yearly. We confirm your final price after a short chat.</p>
        <button type="button" class="btn btn--light btn--block" wire:click="useEstimate" x-on:click="setTimeout(() => document.getElementById('contact')?.scrollIntoView({ behavior: 'smooth' }), 150)">
            Get this quote <x-site-icon name="arrow-right" />
        </button>
    </aside>
</div>
