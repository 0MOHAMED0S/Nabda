<?php

namespace App\Mail;

use App\Models\FoundationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewFoundationMessageAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $foundationMessage;

    /**
     * استقبال الرسالة
     */
    public function __construct(FoundationMessage $foundationMessage)
    {
        $this->foundationMessage = $foundationMessage;
    }

    /**
     * عنوان الإيميل الذي سيصل للمؤسسة
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رسالة تواصل جديدة: ' . $this->foundationMessage->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new_foundation_message',
        );
    }
}
