<?php

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceIssued;
use App\Mail\InvoicePaid;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config(['services.paystack.secret_key' => 'sk_test_secret', 'services.paystack.public_key' => 'pk_test_public']);
});

/**
 * A Paystack transaction as returned by verify or sent in a webhook.
 *
 * @return array<string, mixed>
 */
function paystackPayment(Invoice $invoice, array $overrides = []): array
{
    return array_merge([
        'status' => 'success',
        'reference' => $invoice->number.'-ABCD1234',
        'amount' => $invoice->amount,
        'currency' => 'NGN',
        'metadata' => ['invoice_id' => $invoice->id],
    ], $overrides);
}

test('the admin creates an invoice in naira, stored in kobo, and emails it with the PDF', function () {
    $project = Project::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->withQueryParams(['project' => $project->id])
        ->test('pages::admin.invoices.form')
        ->assertSet('clientId', (string) $project->user_id)
        ->set('items', [
            ['description' => 'Deposit', 'amount' => '150,000'],
            ['description' => 'Domain', 'amount' => '15000.50'],
        ])
        ->call('save', true)
        ->assertHasNoErrors();

    $invoice = Invoice::query()->sole();

    expect($invoice)
        ->number->toBe('INV-'.now()->year.'-0001')
        ->amount->toBe(16500050)
        ->project_id->toBe($project->id)
        ->sent_at->not->toBeNull()
        ->and($invoice->items[1])->toBe(['description' => 'Domain', 'amount' => 1500050]);

    Mail::assertSent(InvoiceIssued::class, fn (InvoiceIssued $mail) => $mail->hasTo($project->client->email)
        && $mail->attachments()[0]->as === 'invoice-'.$invoice->number.'.pdf');
});

test('invoice numbers keep counting after one is deleted', function () {
    Invoice::factory()->count(2)->create();
    Invoice::query()->first()->delete();

    expect(Invoice::factory()->create()->number)->toBe('INV-'.now()->year.'-0003');
});

test('an invoice needs a total above zero', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.invoices.form')
        ->set('clientId', (string) User::factory()->create()->id)
        ->set('title', 'Deposit')
        ->set('items', [['description' => 'Deposit', 'amount' => '0']])
        ->call('save')
        ->assertHasErrors('items');

    expect(Invoice::query()->count())->toBe(0);
});

test('the admin can mark a bank transfer as paid and the client gets a receipt', function () {
    $invoice = Invoice::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.invoices.form', ['invoice' => $invoice])
        ->set('paymentMethod', 'bank_transfer')
        ->set('paymentReference', 'TRF-889')
        ->call('markPaid')
        ->assertHasNoErrors();

    expect($invoice->refresh())
        ->status->toBe(InvoiceStatus::Paid)
        ->payment_reference->toBe('TRF-889');

    Mail::assertSent(InvoicePaid::class, fn (InvoicePaid $mail) => $mail->hasTo($invoice->client->email));
});

test('a paid invoice cannot be changed', function () {
    $invoice = Invoice::factory()->paid()->create(['title' => 'Deposit']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test('pages::admin.invoices.form', ['invoice' => $invoice])
        ->set('title', 'Changed')
        ->call('save')
        ->assertForbidden();

    expect($invoice->refresh()->title)->toBe('Deposit');
});

test('a client is sent to Paystack to pay their invoice', function () {
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
        'status' => true,
        'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123'],
    ])]);
    $invoice = Invoice::factory()->create(['amount' => 7500000]);

    $this->actingAs($invoice->client)
        ->post(route('portal.invoices.pay', $invoice))
        ->assertRedirect('https://checkout.paystack.com/abc123');

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer sk_test_secret')
        && $request['amount'] === 7500000
        && $request['email'] === $invoice->client->email
        && $request['metadata']['invoice_id'] === $invoice->id);
});

test('a client cannot pay another client\'s invoice', function () {
    Http::fake();
    $invoice = Invoice::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('portal.invoices.pay', $invoice))
        ->assertNotFound();

    Http::assertNothingSent();
});

test('without Paystack keys the client is asked to pay by bank transfer', function () {
    config(['services.paystack.secret_key' => null]);
    $invoice = Invoice::factory()->create();

    $this->actingAs($invoice->client)
        ->post(route('portal.invoices.pay', $invoice))
        ->assertRedirect(route('portal.invoices.show', $invoice))
        ->assertSessionHas('payment_error');

    $this->actingAs($invoice->client)
        ->get(route('portal.invoices.show', $invoice))
        ->assertDontSee('with Paystack');
});

test('the invoice is marked paid when Paystack confirms the payment on return', function () {
    $invoice = Invoice::factory()->create();
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackPayment($invoice)])]);

    $this->actingAs($invoice->client)
        ->get(route('portal.payments.callback', ['reference' => $invoice->number.'-ABCD1234']))
        ->assertRedirect(route('portal.invoices.show', $invoice))
        ->assertSessionHas('payment_success');

    expect($invoice->refresh())
        ->status->toBe(InvoiceStatus::Paid)
        ->payment_method->toBe('paystack')
        ->payment_reference->toBe($invoice->number.'-ABCD1234');

    Mail::assertSent(InvoicePaid::class, 1);
});

test('a payment for less than the invoice total does not mark it paid', function () {
    $invoice = Invoice::factory()->create(['amount' => 7500000]);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackPayment($invoice, ['amount' => 100])])]);

    $this->actingAs($invoice->client)
        ->get(route('portal.payments.callback', ['reference' => 'X']))
        ->assertSessionHas('payment_error');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('a signed Paystack webhook marks the invoice paid only once', function () {
    $invoice = Invoice::factory()->create();
    $payload = json_encode(['event' => 'charge.success', 'data' => paystackPayment($invoice)]);
    $signature = hash_hmac('sha512', $payload, 'sk_test_secret');

    foreach ([1, 2] as $attempt) {
        $this->call('POST', route('paystack.webhook'), [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk();
    }

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
    Mail::assertSent(InvoicePaid::class, 1);
});

test('a webhook with a wrong signature is ignored', function () {
    $invoice = Invoice::factory()->create();
    $payload = json_encode(['event' => 'charge.success', 'data' => paystackPayment($invoice)]);

    $this->call('POST', route('paystack.webhook'), [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'forged', 'CONTENT_TYPE' => 'application/json'], $payload)
        ->assertUnauthorized();

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('the client and the admin can download the invoice PDF', function () {
    $invoice = Invoice::factory()->paid()->create();

    $this->actingAs($invoice->client)
        ->get(route('portal.invoices.pdf', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('receipt-'.$invoice->number.'.pdf');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.invoices.pdf', $invoice))
        ->assertOk();
});
