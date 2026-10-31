<?php

namespace App\Support;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Builds the invoice PDF, which doubles as the receipt once the invoice is paid.
 */
class InvoicePdf
{
    /**
     * Render the PDF for an invoice.
     */
    public static function make(Invoice $invoice): DomPdf
    {
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->loadMissing('client', 'project'),
            'business' => Setting::values(),
            'bankDetails' => Setting::get('bank_details'),
        ])->setPaper('a4');
    }

    /**
     * The download name, e.g. "receipt-INV-2026-0001.pdf".
     */
    public static function filename(Invoice $invoice): string
    {
        return ($invoice->status === InvoiceStatus::Paid ? 'receipt-' : 'invoice-').$invoice->number.'.pdf';
    }
}
