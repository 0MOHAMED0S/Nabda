<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FoundationMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public $replySubject;
    public $replyBody;
    public $foundationName;

    public function __construct($replySubject, $replyBody, $foundationName)
    {
        $this->replySubject = $replySubject;
        $this->replyBody = $replyBody;
        $this->foundationName = $foundationName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replySubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.foundation_message_reply',
        );
    }
}
