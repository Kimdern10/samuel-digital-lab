<?php

use App\Enums\ProjectStage;
use App\Models\Project;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component {
    public Project $project;

    public string $title = '';

    public string $stage = '';

    public string $previewUrl = '';

    public string $liveUrl = '';

    public string $dueOn = '';

    public string $update = '';

    /**
     * Load the project into the form.
     */
    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->title = $project->title;
        $this->stage = $project->stage->value;
        $this->previewUrl = (string) $project->preview_url;
        $this->liveUrl = (string) $project->live_url;
        $this->dueOn = (string) $project->due_on?->format('Y-m-d');
    }

    /**
     * Save the project details. Moving to a new stage also posts an update the client sees.
     */
    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'stage' => ['required', Rule::enum(ProjectStage::class)],
            'previewUrl' => ['nullable', 'url:http,https', 'max:255'],
            'liveUrl' => ['nullable', 'url:http,https', 'max:255'],
            'dueOn' => ['nullable', 'date'],
        ]);

        $previousStage = $this->project->stage;

        $this->project->update([
            'title' => trim($this->title),
            'stage' => $this->stage,
            'preview_url' => trim($this->previewUrl) ?: null,
            'live_url' => trim($this->liveUrl) ?: null,
            'due_on' => $this->dueOn ?: null,
        ]);

        if ($this->project->stage !== $previousStage) {
            $this->project->updates()->create([
                'body' => __('Your project moved to :stage. :description', ['stage' => $this->project->stage->label(), 'description' => $this->project->stage->description()]),
            ]);
        }

        Flux::toast(variant: 'success', text: __('Project saved.'));
    }

    /**
     * Post a progress note to the client's portal.
     */
    public function postUpdate(): void
    {
        $this->validate(['update' => ['required', 'string', 'max:3000']]);

        $this->project->updates()->create(['body' => trim($this->update)]);
        $this->reset('update');

        Flux::toast(variant: 'success', text: __('Update posted. The client can see it now.'));
    }

    /**
     * Remove a progress note.
     */
    public function deleteUpdate(int $id): void
    {
        $this->project->updates()->whereKey($id)->delete();
    }

    /**
     * Delete the project (its invoices are kept).
     */
    public function delete(): void
    {
        $this->project->delete();

        Flux::toast(variant: 'success', text: __('Project deleted.'));
        $this->redirectRoute('admin.projects.index', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:link :href="route('admin.projects.index')" wire:navigate class="text-sm">← {{ __('All projects') }}</flux:link>
            <flux:heading size="xl" level="1" class="mt-2">{{ $project->title }}</flux:heading>
            <flux:text class="mt-1">{{ __('For :name (:email)', ['name' => $project->client->name, 'email' => $project->client->email]) }}</flux:text>
        </div>
        <flux:button icon="banknotes" :href="route('admin.invoices.create', ['project' => $project->id])" wire:navigate>{{ __('New invoice') }}</flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="grid content-start gap-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <flux:input wire:model="title" :label="__('Project name')" required />
            <flux:select wire:model="stage" :label="__('Stage')" :description="__('The client sees this as a progress bar.')">
                @foreach (\App\Enums\ProjectStage::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="previewUrl" type="url" :label="__('Preview link')" :description="__('Where the client can see the website while you build it.')" placeholder="https://" />
            <flux:input wire:model="liveUrl" type="url" :label="__('Live website')" placeholder="https://" />
            <flux:input wire:model="dueOn" type="date" :label="__('Expected launch date')" />
            <div class="flex justify-between gap-2">
                <flux:button variant="danger" icon="trash" wire:click="delete" wire:confirm="{{ __('Delete this project and its updates?') }}">{{ __('Delete') }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>

        <div class="grid content-start gap-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Updates for the client') }}</flux:heading>
            <form wire:submit="postUpdate" class="grid gap-3">
                <flux:textarea wire:model="update" rows="3" :placeholder="__('e.g. The home page design is ready on the preview link. Tell us what you think.')" />
                <div class="flex justify-end"><flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Post update') }}</flux:button></div>
            </form>

            @forelse ($project->updates as $projectUpdate)
                <div wire:key="update-{{ $projectUpdate->id }}" class="flex items-start justify-between gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <div>
                        <div class="text-xs text-zinc-500">{{ $projectUpdate->created_at?->format('j M Y, g:ia') }}</div>
                        <div class="mt-1 whitespace-pre-line text-sm">{{ $projectUpdate->body }}</div>
                    </div>
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="deleteUpdate({{ $projectUpdate->id }})" :aria-label="__('Remove')" />
                </div>
            @empty
                <flux:text>{{ __('No updates yet.') }}</flux:text>
            @endforelse
        </div>
    </div>

    @if ($project->invoices->isNotEmpty())
        <div class="grid gap-3">
            <flux:heading size="lg">{{ __('Invoices') }}</flux:heading>
            @foreach ($project->invoices as $invoice)
                <a wire:key="invoice-{{ $invoice->id }}" href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40">
                    <span>{{ $invoice->number }} · {{ $invoice->title }}</span>
                    <span class="flex items-center gap-3">{{ \App\Models\Invoice::naira($invoice->amount) }} <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge></span>
                </a>
            @endforeach
        </div>
    @endif
</div>
