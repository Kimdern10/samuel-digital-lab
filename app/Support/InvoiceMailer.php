<?php

namespace App\Support;

use App\Mail\InvoiceIssued;
use App\Mail\InvoicePaid;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

/**
 * Emails invoices and receipts straight away (no queue). A mail failure is logged
 * instead of breaking the page, since the invoice itself is already saved.
 */
class InvoiceMailer
{
    /**
     * Email the invoice to the client and remember when it was sent.
     */
    public static function sendInvoice(Invoice $invoice): bool
    {
        try {
            Mail::to($invoice->client->email)->send(new InvoiceIssued($invoice));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        $invoice->update(['sent_at' => now()]);

        return true;
    }

    /**
     * Email the PDF receipt to the client, with a copy to the business.
     */
    public static function sendReceipt(Invoice $invoice): bool
    {
        try {
            Mail::to($invoice->client->email)->bcc(Setting::get('email'))->send(new InvoicePaid($invoice));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }

        return true;
    }
}
