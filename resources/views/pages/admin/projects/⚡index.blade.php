<?php

use App\Enums\ProjectStage;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Projects')] class extends Component {
    #[Url(as: 'client', except: '')]
    public string $clientId = '';

    public string $title = '';

    /**
     * Open the new-project form straight away when coming from a client's "Project" button.
     */
    public function mount(): void
    {
        if ($this->clientId !== '') {
            Flux::modal('project-form')->show();
        }
    }

    /**
     * Every project, newest first.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->with('client')->latest('id')->get();
    }

    /**
     * Clients a project can be for.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function clients(): Collection
    {
        return User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Create the project and open it.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'clientId' => ['required', Rule::exists('users', 'id')->where('is_admin', false)],
            'title' => ['required', 'string', 'max:150'],
        ], [], ['clientId' => 'client']);

        $project = Project::query()->create([
            'user_id' => (int) $validated['clientId'],
            'title' => trim($validated['title']),
            'stage' => ProjectStage::Planning,
        ]);

        $this->redirectRoute('admin.projects.show', $project, navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Projects') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Websites you are building. Clients see the stage, preview link and updates in their portal.') }}</flux:text>
        </div>
        <flux:modal.trigger name="project-form">
            <flux:button variant="primary" icon="plus">{{ __('New project') }}</flux:button>
        </flux:modal.trigger>
    </div>

    @if ($this->projects->isEmpty())
        <flux:callout icon="rectangle-stack">{{ __('No projects yet. Add a client first, then start their project.') }}</flux:callout>
    @else
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->projects as $project)
                <a wire:key="project-{{ $project->id }}" href="{{ route('admin.projects.show', $project) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-4 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $project->title }}</div>
                        <div class="text-sm text-zinc-500">{{ $project->client->name }}@if ($project->due_on) · {{ __('Due :date', ['date' => $project->due_on->format('j M Y')]) }}@endif</div>
                    </div>
                    <flux:badge :color="$project->stage === \App\Enums\ProjectStage::Launched ? 'green' : 'blue'">{{ $project->stage->label() }}</flux:badge>
                </a>
            @endforeach
        </div>
    @endif

    <flux:modal name="project-form" class="w-full max-w-md">
        <form wire:submit="save" class="grid gap-5">
            <flux:heading size="lg">{{ __('New project') }}</flux:heading>
            @if ($this->clients->isEmpty())
                <flux:callout icon="users">
                    {{ __('Add a client first.') }}
                    <x-slot name="actions"><flux:button size="sm" :href="route('admin.clients')" wire:navigate>{{ __('Go to clients') }}</flux:button></x-slot>
                </flux:callout>
            @else
                <flux:select wire:model="clientId" :label="__('Client')" :placeholder="__('Choose a client')">
                    @foreach ($this->clients as $client)
                        <flux:select.option :value="$client->id">{{ $client->name }} ({{ $client->email }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="title" :label="__('Project name')" :placeholder="__('e.g. Eze Foods business website')" required />
                <div class="flex justify-end gap-2">
                    <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Create project') }}</flux:button>
                </div>
            @endif
        </form>
    </flux:modal>
</div>
