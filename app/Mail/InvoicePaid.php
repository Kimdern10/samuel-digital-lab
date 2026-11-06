<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Setting;
use App\Support\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Thanks a client for paying and sends the PDF receipt. Sent straight away (no queue).
 */
class InvoicePaid extends Mailable
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
            subject: 'Payment received: '.$this->invoice->number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-paid',
            with: [
                'businessName' => Setting::get('business_name'),
                'invoiceUrl' => $this->invoice->payLink(),
            ],
        );
    }

    /**
     * Attach the PDF receipt.
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
