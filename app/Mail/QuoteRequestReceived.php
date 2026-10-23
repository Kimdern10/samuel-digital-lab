<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Support\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent straight away (not queued) so it works on shared hosting without a queue worker.
 */
class QuoteRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public QuoteRequest $quote) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New quote request from '.$this->quote->name,
            replyTo: $this->quote->email ? [new Address($this->quote->email, $this->quote->name)] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quote-request-received',
            with: [
                'whatsAppUrl' => WhatsApp::link($this->quote->phone, "Hello {$this->quote->name}, thank you for your quote request on Samuel Digital Lab."),
                'adminUrl' => route('admin.quotes.show', $this->quote),
            ],
        );
    }
}
