<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Paystack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaystackController extends Controller
{
    /**
     * Send the client to Paystack to pay one of their invoices.
     */
    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()?->id, Response::HTTP_NOT_FOUND);

        if (! $invoice->isPayable()) {
            return redirect()->route('portal.invoices.show', $invoice);
        }

        if (! Paystack::enabled()) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('payment_error', 'Online payment is not switched on yet. Please pay by bank transfer.');
        }

        try {
            $url = Paystack::initialize($invoice, $request->user()->email, route('portal.payments.callback'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('portal.invoices.show', $invoice)
                ->with('payment_error', 'We could not reach Paystack. Please try again in a minute.');
        }

        return redirect()->away($url);
    }

    /**
     * Paystack sends the client back here after paying. Check the payment with Paystack itself.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $invoice = $reference !== '' && Paystack::enabled() ? Paystack::confirm($reference) : null;

        if ($invoice === null || $invoice->user_id !== $request->user()?->id) {
            return redirect()->route('portal.invoices')
                ->with('payment_error', 'We could not confirm that payment. If money left your account, message us and we will check it.');
        }

        return redirect()->route('portal.invoices.show', $invoice)
            ->with('payment_success', 'Payment received. Thank you! Your receipt is on its way to your email.');
    }

    /**
     * Paystack's signed notice of a payment, so invoices get marked paid even if the client closes the tab.
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
}
