<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use App\Support\InvoicePdf;
use Illuminate\Http\Response;

/**
 * The invoice page reached from the signed link in the invoice email, so clients can pay without logging in.
 */
class InvoiceLinkController extends Controller
{
    /**
     * Show the invoice with its pay button.
     */
    public function show(Invoice $invoice): Response
    {
        return response()
            ->view('invoices.show', [
                'invoice' => $invoice->loadMissing('client', 'project'),
                'bankDetails' => Setting::get('bank_details'),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Download the invoice, or the receipt once paid, as a PDF.
     */
    public function pdf(Invoice $invoice): Response
    {
        return InvoicePdf::make($invoice)->download(InvoicePdf::filename($invoice));
    }
}
