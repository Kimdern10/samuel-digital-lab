<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Paystack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class PaystackController extends Controller
{
    /**
     * Send a logged-in client to Paystack to pay one of their invoices.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()?->id, Response::HTTP_NOT_FOUND);

        return $this->startPayment($invoice, route('portal.invoices.show', $invoice));
    }

    /**
     * Send someone with the invoice's pay link (no login needed) to Paystack.
     */
    public function payByLink(Invoice $invoice): RedirectResponse
    {
        return $this->startPayment($invoice, $invoice->payLink());
    }

    /**
     * Paystack sends the payer back here after paying. Check the payment with Paystack itself.
     */
    public function callback(Request $request, Invoice $invoice): RedirectResponse
    {
        // Paystack adds ?trxref=…&reference=… to the signed address, so those are left out of the check.
        abort_unless($request->hasValidSignatureWhileIgnoring(['trxref', 'reference']), Response::HTTP_FORBIDDEN);

        $backUrl = $invoice->user_id === $request->user()?->id ? route('portal.invoices.show', $invoice) : $invoice->payLink();
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $paidInvoice = $reference !== '' && Paystack::enabled() ? Paystack::confirm($reference) : null;

        if ($paidInvoice === null || ! $paidInvoice->is($invoice)) {
            return redirect()->to($backUrl)
                ->with('payment_error', 'We could not confirm that payment. If money left your account, message us and we will check it.');
        }

        return redirect()->to($backUrl)
            ->with('payment_success', 'Payment received. Thank you! Your receipt is on its way to your email.');
    }

    /**
     * Paystack's signed notice of a payment, so invoices get marked paid even if the payer closes the tab.
     */
    public function webhook(Request $request): Response
    {
        if (! Paystack::hasValidSignature($request->getContent(), $request->header('x-paystack-signature'))) {
            return response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        if ($request->input('event') === 'charge.success') {
            Paystack::settle((array) $request->input('data', []));
        }

        return response('OK');
    }

    /**
     * Start a Paystack payment, or go back to the invoice when that is not possible.
     */
    private function startPayment(Invoice $invoice, string $backUrl): RedirectResponse
    {
        if (! $invoice->isPayable()) {
            return redirect()->to($backUrl);
        }

        if (! Paystack::enabled()) {
            return redirect()->to($backUrl)
                ->with('payment_error', 'Online payment is not switched on yet. Please pay by bank transfer.');
        }

        try {
            $url = Paystack::initialize($invoice, $invoice->client->email, URL::signedRoute('payments.callback', $invoice));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->to($backUrl)
                ->with('payment_error', 'We could not reach Paystack. Please try again in a minute.');
        }

        return redirect()->away($url);
    }
}
