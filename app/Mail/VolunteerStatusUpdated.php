<?php

namespace App\Mail;

use App\Models\Volunteer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class VolunteerStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $volunteer;

    /**
     * استقبال بيانات المتطوع
     */
    public function __construct(Volunteer $volunteer)
    {
        $this->volunteer = $volunteer;
    }

    /**
     * عنوان الإيميل
     */
    public function envelope(): Envelope
    {
        $statusAr = $this->volunteer->status === 'approved' ? 'مقبول' : 'مرفوض';

        return new Envelope(
            from: new Address('contact@nabdatkhair.com', 'نبضة خير'),
            subject: 'تحديث حالة طلب التطوع - ' . $statusAr,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.volunteer_status',
        );
    }
}
