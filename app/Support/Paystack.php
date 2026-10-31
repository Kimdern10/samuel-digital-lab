<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Talks to Paystack to take invoice payments. Payments are confirmed by asking Paystack
 * directly (verify) or by its signed webhook, never by trusting the browser.
 */
class Paystack
{
    private const string BASE_URL = 'https://api.paystack.co';

    /**
     * Whether online payments are switched on (the secret key is set).
     */
    public static function enabled(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * Start a payment and return the Paystack page to send the client to.
     *
     * @throws RuntimeException when Paystack refuses the request
     */
    public static function initialize(Invoice $invoice, string $email, string $callbackUrl): string
    {
        $response = self::client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $invoice->amount,
            'currency' => 'NGN',
            'reference' => $invoice->number.'-'.Str::upper(Str::random(8)),
            'callback_url' => $callbackUrl,
            'metadata' => ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->number],
        ]);

        $url = $response->json('data.authorization_url');

        if (! $response->successful() || ! is_string($url)) {
            throw new RuntimeException('Paystack could not start the payment: '.$response->json('message', $response->status()));
        }

        return $url;
    }

    /**
     * Ask Paystack about a payment and mark its invoice paid when the full amount came in.
     * Returns the invoice the payment was for, or null if it was not a successful payment.
     */
    public static function confirm(string $reference): ?Invoice
    {
        $response = self::client()->get('/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $payment */
        $payment = (array) $response->json('data', []);

        return self::settle($payment);
    }

    /**
     * Mark the invoice in a successful Paystack payment as paid.
     *
     * @param  array<string, mixed>  $payment  The "data" Paystack sends for a transaction
     */
    public static function settle(array $payment): ?Invoice
    {
        $invoiceId = data_get($payment, 'metadata.invoice_id');
        $invoice = is_numeric($invoiceId) ? Invoice::query()->find((int) $invoiceId) : null;

        if ($invoice === null
            || data_get($payment, 'status') !== 'success'
            || data_get($payment, 'currency') !== 'NGN'
            || (int) data_get($payment, 'amount') < $invoice->amount) {
            return null;
        }

        if ($invoice->markPaid('paystack', (string) data_get($payment, 'reference'))) {
            InvoiceMailer::sendReceipt($invoice);
        }

        return $invoice;
    }

    /**
     * Check that a webhook really came from Paystack.
     */
    public static function hasValidSignature(string $payload, ?string $signature): bool
    {
        if (! self::enabled() || blank($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, (string) config('services.paystack.secret_key')), $signature);
    }

    /**
     * An HTTP client signed in with the secret key.
     */
    private static function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->timeout(20);
    }
}
