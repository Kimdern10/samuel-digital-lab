<?php

use App\Models\Project;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My website')] class extends Component {
    public Project $project;

    /**
     * Show the project, but only to the client it belongs to.
     */
    public function mount(Project $project): void
    {
        abort_unless($project->user_id === auth()->id(), 404);

        $this->project = $project;
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:link :href="route('portal')" wire:navigate class="text-sm">← {{ __('My website') }}</flux:link>
        <flux:heading size="xl" level="1" class="mt-2">{{ $project->title }}</flux:heading>
        @if ($project->due_on && $project->stage !== \App\Enums\ProjectStage::Launched)
            <flux:text class="mt-1">{{ __('Expected launch: :date', ['date' => $project->due_on->format('j F Y')]) }}</flux:text>
        @endif
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <x-project-progress :stage="$project->stage" />
        <flux:text>{{ $project->stage->description() }}</flux:text>
        <div class="flex flex-wrap gap-2">
            @if ($project->live_url)
                <flux:button variant="primary" icon="globe-alt" :href="$project->live_url" target="_blank" rel="noopener">{{ __('Open your live website') }}</flux:button>
            @endif
            @if ($project->preview_url && $project->stage !== \App\Enums\ProjectStage::Launched)
                <flux:button :variant="$project->live_url ? 'filled' : 'primary'" icon="eye" :href="$project->preview_url" target="_blank" rel="noopener">{{ __('Open the preview') }}</flux:button>
            @endif
        </div>
    </div>

    <div class="grid gap-3">
        <flux:heading size="lg">{{ __('Updates') }}</flux:heading>
        @forelse ($project->updates as $projectUpdate)
            <div wire:key="update-{{ $projectUpdate->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs text-zinc-500">{{ $projectUpdate->created_at?->format('j M Y, g:ia') }}</div>
                <div class="mt-1 whitespace-pre-line text-sm">{{ $projectUpdate->body }}</div>
            </div>
        @empty
            <flux:text>{{ __('No updates yet. We will post here as work moves along.') }}</flux:text>
        @endforelse
    </div>

    @if ($project->invoices->isNotEmpty())
        <div class="grid gap-3">
            <flux:heading size="lg">{{ __('Invoices') }}</flux:heading>
            @foreach ($project->invoices as $invoice)
                <x-portal-invoice-row :invoice="$invoice" wire:key="invoice-{{ $invoice->id }}" />
            @endforeach
        </div>
    @endif
</div>
