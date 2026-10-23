@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2">
    <flux:heading size="xl" level="1" class="text-3xl! font-extrabold! tracking-tight">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
