<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My website')] class extends Component {
    /**
     * The client's projects with their updates.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return auth()->user()->projects()->with('updates')->get();
    }

    /**
     * Invoices still waiting for payment.
     *
     * @return Collection<int, Invoice>
     */
    #[Computed]
    public function unpaidInvoices(): Collection
    {
        return auth()->user()->invoices()->where('status', InvoiceStatus::Unpaid)->get();
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:text class="mt-1">{{ __('Follow your website from planning to launch, and pay your invoices here.') }}</flux:text>
    </div>

    @foreach ($this->unpaidInvoices as $invoice)
        <flux:callout wire:key="due-{{ $invoice->id }}" icon="banknotes" color="amber">
            <flux:callout.heading>{{ __(':title: :amount to pay', ['title' => $invoice->title, 'amount' => \App\Models\Invoice::naira($invoice->amount)]) }}</flux:callout.heading>
            @if ($invoice->due_on)
                <flux:callout.text>{{ __('Due :date', ['date' => $invoice->due_on->format('j F Y')]) }}</flux:callout.text>
            @endif
            <x-slot name="actions">
                <flux:button size="sm" variant="primary" :href="route('portal.invoices.show', $invoice)" wire:navigate>{{ __('View and pay') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endforeach

    @forelse ($this->projects as $project)
        <a wire:key="project-{{ $project->id }}" href="{{ route('portal.projects.show', $project) }}" wire:navigate class="grid gap-4 rounded-xl border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700/40">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">{{ $project->title }}</flux:heading>
                <flux:badge :color="$project->stage === \App\Enums\ProjectStage::Launched ? 'green' : 'blue'">{{ $project->stage->label() }}</flux:badge>
            </div>
            <x-project-progress :stage="$project->stage" />
            @if ($project->updates->isNotEmpty())
                <flux:text>{{ __('Latest update: :text', ['text' => str($project->updates->first()->body)->limit(140)]) }}</flux:text>
            @endif
        </a>
    @empty
        <flux:callout icon="rectangle-stack">{{ __('Your project will show here once we start work on it.') }}</flux:callout>
    @endforelse
</div>
