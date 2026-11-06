<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStage;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * A demo client for trying the client portal on your own computer.
 *
 * Run: php artisan db:seed --class=DemoClientSeeder
 * Log in with demo-client@example.test and the password "password".
 * It will not run on the live site.
 */
class DemoClientSeeder extends Seeder
{
    public const string EMAIL = 'demo-client@example.test';

    /**
     * Create (or refresh) the demo client with a project and invoices.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The demo client is only for trying the portal locally.');
        }

        $client = User::query()->firstOrNew(['email' => self::EMAIL]);
        $client->forceFill([
            'name' => 'Demo Client',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_admin' => false,
        ])->save();

        $project = Project::query()->updateOrCreate(
            ['user_id' => $client->id, 'title' => 'Demo Foods business website'],
            [
                'stage' => ProjectStage::Building,
                'preview_url' => route('home'),
                'live_url' => null,
                'due_on' => now()->addWeeks(2),
            ],
        );

        if ($project->updates()->doesntExist()) {
            $project->updates()->create(['body' => 'We agreed the pages: Home, About, Menu, Order and Contact.', 'created_at' => now()->subDays(6)]);
            $project->updates()->create(['body' => 'The design is approved. Building has started on the preview link.', 'created_at' => now()->subDays(2)]);
        }

        if ($client->invoices()->doesntExist()) {
            Invoice::factory()->for($client, 'client')->paid()->create([
                'project_id' => $project->id,
                'title' => 'Planning fee',
                'items' => [['description' => 'Planning and design', 'amount' => 2000000]],
                'amount' => 2000000,
                'paid_at' => now()->subDays(6),
            ]);

            Invoice::factory()->for($client, 'client')->create([
                'project_id' => $project->id,
                'title' => 'Website deposit (50%)',
                'items' => [['description' => 'Business website: 50% deposit', 'amount' => 10000]],
                'amount' => 10000,
                'status' => InvoiceStatus::Unpaid,
                'notes' => 'A small ₦100 demo invoice for trying Paystack.',
            ]);
        }
    }
}
