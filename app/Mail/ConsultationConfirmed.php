<?php

namespace App\Mail;

use App\Models\Consultation;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirms a booked call to the visitor. Sent straight away (no queue).
 */
class ConsultationConfirmed extends Mailable
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
            subject: 'Your call with '.Setting::get('business_name').' is booked',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.consultation-confirmed',
            with: [
                'businessName' => Setting::get('business_name'),
                'calendarUrl' => $this->consultation->googleCalendarUrl('Call with '.Setting::get('business_name')),
                'whatsApp' => Setting::get('whatsapp_primary'),
            ],
        );
    }
}
