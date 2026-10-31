<?php

use App\Enums\ProjectStage;
use App\Mail\ClientInvited;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('clients land in their portal after logging in and admins land on the dashboard', function () {
    $client = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $client->email, 'password' => 'password'])
        ->assertRedirect(route('portal', absolute: false));

    auth()->logout();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});

test('a client who first tried an admin page is not sent back there after logging in', function () {
    $client = User::factory()->create();

    $this->get(route('admin.quotes.index'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $client->email, 'password' => 'password'])
        ->assertRedirect(route('portal', absolute: false));
});

test('clients cannot open the admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.clients'))
        ->assertForbidden();
});

test('the portal shows the client their own projects and unpaid invoices only', function () {
    $client = User::factory()->create();
    Project::factory()->for($client, 'client')->create(['title' => 'Eze Foods website', 'stage' => ProjectStage::Design]);
    Invoice::factory()->for($client, 'client')->create(['title' => 'Website deposit (50%)']);
    Project::factory()->create(['title' => 'Someone else website']);

    $this->actingAs($client)
        ->get(route('portal'))
        ->assertOk()
        ->assertSee('Eze Foods website')
        ->assertSee('Design')
        ->assertSee('Website deposit (50%)')
        ->assertDontSee('Someone else website');
});

test('a client can follow their project with its preview link and updates', function () {
    $project = Project::factory()->create(['preview_url' => 'https://preview.eze.test', 'stage' => ProjectStage::Building]);
    $project->updates()->create(['body' => 'The home page is ready to check.']);

    $this->actingAs($project->client)
        ->get(route('portal.projects.show', $project))
        ->assertOk()
        ->assertSee('https://preview.eze.test')
        ->assertSee('The home page is ready to check.')
        ->assertSee(ProjectStage::Building->description());
});

test('a client cannot open another client\'s project or invoice', function () {
    $intruder = User::factory()->create();
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($intruder)->get(route('portal.projects.show', $project))->assertNotFound();
    $this->actingAs($intruder)->get(route('portal.invoices.show', $invoice))->assertNotFound();
    $this->actingAs($intruder)->get(route('portal.invoices.pdf', $invoice))->assertNotFound();
});

test('the admin can add a client and email them a link to set their password', function () {
    Mail::fake();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.clients')
        ->set('name', 'Ngozi Eze')
        ->set('email', ' Ngozi@Example.com ')
        ->call('save')
        ->assertHasNoErrors();

    $client = User::query()->where('email', 'ngozi@example.com')->sole();

    expect($client->is_admin)->toBeFalse()
        ->and($client->hasVerifiedEmail())->toBeTrue();

    Mail::assertSent(ClientInvited::class, fn (ClientInvited $mail) => $mail->hasTo('ngozi@example.com')
        && str_contains($mail->setPasswordUrl, '/reset-password/'));
});

test('a client email can only be used once', function () {
    User::factory()->create(['email' => 'ngozi@example.com']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.clients')
        ->set('name', 'Ngozi Eze')
        ->set('email', 'ngozi@example.com')
        ->call('save')
        ->assertHasErrors('email');
});

test('moving a project to a new stage posts an update for the client', function () {
    $project = Project::factory()->create(['stage' => ProjectStage::Building]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.projects.show', ['project' => $project])
        ->set('stage', ProjectStage::Review->value)
        ->set('previewUrl', 'https://preview.eze.test')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->refresh())
        ->stage->toBe(ProjectStage::Review)
        ->preview_url->toBe('https://preview.eze.test')
        ->and($project->updates()->sole()->body)->toContain('Your review');
});

test('the admin can post a progress update', function () {
    $project = Project::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.projects.show', ['project' => $project])
        ->set('update', 'Design is ready on the preview link.')
        ->call('postUpdate')
        ->assertHasNoErrors();

    expect($project->updates()->sole()->body)->toBe('Design is ready on the preview link.');
});

test('a project can only be created for a client', function () {
    $admin = User::factory()->admin()->create();
    $client = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.projects.index')
        ->set('clientId', (string) $admin->id)
        ->set('title', 'Wrong')
        ->call('save')
        ->assertHasErrors('clientId');

    Livewire::actingAs($admin)
        ->test('pages::admin.projects.index')
        ->set('clientId', (string) $client->id)
        ->set('title', 'Eze Foods website')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.projects.show', Project::query()->sole()));
});
