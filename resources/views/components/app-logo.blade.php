@props([
    'sidebar' => false,
])

@php
    $logoUrl = \App\Models\Setting::logoUrl();
    $name = \App\Models\Setting::get('business_name');
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$name" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md text-sm font-bold text-white">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="size-full bg-white object-contain p-0.5">
            @else
                <img src="{{ asset('brand/logo-icon.svg') }}" alt="" class="size-full">
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$name" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md text-sm font-bold text-white">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="size-full bg-white object-contain p-0.5">
            @else
                <img src="{{ asset('brand/logo-icon.svg') }}" alt="" class="size-full">
            @endif
        </x-slot>
    </flux:brand>
@endif
