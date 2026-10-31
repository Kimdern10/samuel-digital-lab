<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Setting;
use App\Support\InvoicePdf;
use App\Support\Paystack;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sends a client their invoice with a link to pay it. Sent straight away (no queue).
 */
class InvoiceIssued extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Invoice $invoice) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice '.$this->invoice->number.' from '.Setting::get('business_name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-issued',
            with: [
                'businessName' => Setting::get('business_name'),
                'invoiceUrl' => route('portal.invoices.show', $this->invoice),
                'canPayOnline' => Paystack::enabled(),
                'bankDetails' => Setting::get('bank_details'),
            ],
        );
    }

    /**
     * Attach the invoice PDF.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => InvoicePdf::make($this->invoice)->output(), InvoicePdf::filename($this->invoice))
                ->withMime('application/pdf'),
        ];
    }
}
