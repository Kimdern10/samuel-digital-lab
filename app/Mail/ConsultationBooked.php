<?php

namespace App\Mail;

use App\Models\Consultation;
use App\Support\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the owner about a newly booked call. Sent straight away (no queue).
 */
class ConsultationBooked extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Consultation $consultation) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New call booked: '.$this->consultation->name.', '.$this->consultation->localStart()->format('D j M, g:ia'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.consultation-booked',
            with: [
                'whatsAppUrl' => WhatsApp::link($this->consultation->phone),
                'adminUrl' => route('admin.consultations'),
            ],
        );
    }
}
