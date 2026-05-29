<?php

namespace App\Mail;

use App\Models\Foundation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class FoundationStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $foundation;

    /**
     * استقبال بيانات المؤسسة عند استدعاء الإيميل
     */
    public function __construct(Foundation $foundation)
    {
        $this->foundation = $foundation;
    }

    /**
     * عنوان الإيميل والمرسل
     */
    public function envelope(): Envelope
    {
        $statusAr = $this->foundation->approval_status === 'approved' ? 'مقبول' : 'مرفوض';

        return new Envelope(
            from: new Address('contact@nabdatkhair.com', 'نبضة خير'),
            subject: 'تحديث حالة حساب مؤسستكم - ' . $statusAr,
        );
    }

    /**
     * تحديد ملف الـ Blade الذي يحتوي على تصميم الإيميل
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.foundation_status',
        );
    }
}
