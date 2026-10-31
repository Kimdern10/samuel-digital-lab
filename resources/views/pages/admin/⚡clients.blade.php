<?php

use App\Mail\ClientInvited;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Clients')] class extends Component {
    public string $name = '';

    public string $email = '';

    public bool $sendInvite = true;

    /**
     * Every client (users who are not the admin).
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function clients(): Collection
    {
        return User::query()->where('is_admin', false)->withCount(['projects', 'invoices'])->orderBy('name')->get();
    }

    /**
     * Open the form for a new client.
     */
    public function create(): void
    {
        $this->reset('name', 'email', 'sendInvite');
        $this->resetValidation();

        Flux::modal('client-form')->show();
    }

    /**
     * Create the client's account and, if chosen, email them a link to set their password.
     */
    public function save(): void
    {
        $this->email = strtolower(trim($this->email));

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
        ], [
            'email.unique' => 'There is already an account with this email.',
        ]);

        $client = new User;
        $client->forceFill([
            'name' => trim($this->name),
            'email' => $this->email,
            'password' => Str::password(32),
            'email_verified_at' => now(),
            'is_admin' => false,
        ])->save();

        if ($this->sendInvite) {
            $this->invite($client->id);
        } else {
            Flux::toast(variant: 'success', text: __('Client added.'));
        }

        Flux::modal('client-form')->close();
        unset($this->clients);
    }

    /**
     * Email the client a link to set their password and open their portal.
     */
    public function invite(int $id): void
    {
        $client = User::query()->where('is_admin', false)->findOrFail($id);
        $url = route('password.reset', ['token' => Password::broker()->createToken($client), 'email' => $client->email]);

        try {
            Mail::to($client->email)->send(new ClientInvited($client, $url));
            Flux::toast(variant: 'success', text: __('Invite sent to :email.', ['email' => $client->email]));
        } catch (\Throwable $exception) {
            report($exception);
            Flux::toast(variant: 'danger', text: __('The email could not be sent. Check your mail settings.'));
        }
    }

    /**
     * Delete a client with their projects and invoices.
     */
    public function delete(int $id): void
    {
        User::query()->where('is_admin', false)->findOrFail($id)->delete();
        unset($this->clients);

        Flux::toast(variant: 'success', text: __('Client deleted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Clients') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Each client gets a portal to follow their website and pay invoices. They log in on the normal login page.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add a client') }}</flux:button>
    </div>

    @if ($this->clients->isEmpty())
        <flux:callout icon="users">{{ __('No clients yet. Add one when a customer pays their deposit.') }}</flux:callout>
    @else
        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->clients as $client)
                <div wire:key="client-{{ $client->id }}" class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <div class="font-medium">{{ $client->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $client->email }} · {{ trans_choice('{0} no projects|{1} 1 project|[2,*] :count projects', $client->projects_count) }} · {{ trans_choice('{0} no invoices|{1} 1 invoice|[2,*] :count invoices', $client->invoices_count) }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" icon="plus" :href="route('admin.projects.index', ['client' => $client->id])" wire:navigate>{{ __('Project') }}</flux:button>
                        <flux:button size="sm" icon="envelope" wire:click="invite({{ $client->id }})">{{ __('Send login link') }}</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $client->id }})" wire:confirm="{{ __('Delete this client, their projects and invoices? This cannot be undone.') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="client-form" class="w-full max-w-md">
        <form wire:submit="save" class="grid gap-5">
            <flux:heading size="lg">{{ __('Add a client') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g. Ngozi Eze')" required />
            <flux:input wire:model="email" type="email" :label="__('Email')" required />
            <flux:switch wire:model="sendInvite" :label="__('Email them a link to set their password')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Add client') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
