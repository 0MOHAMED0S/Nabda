<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public $replySubject;
    public $replyBody;

    public function __construct($replySubject, $replyBody)
    {
        $this->replySubject = $replySubject;
        $this->replyBody = $replyBody;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رد على رسالتك: ' . $this->replySubject,
        );
    }

    public function content(): Content
    {
        // ستحتاج لإنشاء ملف view بسيط للإيميل (مثلاً: resources/views/emails/contact_reply.blade.php)
        return new Content(
            view: 'emails.contact_reply', // قم بإنشاء هذا الملف لاحقاً وضع فيه تصميم الإيميل
            with: [
                'body' => $this->replyBody,
            ],
        );
    }
}
