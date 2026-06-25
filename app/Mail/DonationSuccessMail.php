<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DonationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $donation;
    public $caseTitle;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Donation $donation, $caseTitle = null)
    {
        $this->donation = $donation;
        $this->caseTitle = $caseTitle;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // تغيير عنوان الإيميل بناءً على نوع التبرع
        $subject = $this->donation->donation_type === 'financial'
            ? 'شكراً لعطائكم الكريم 💙 | نبضة خير'
            : 'تم استلام طلب التبرع العيني 🎁 | نبضة خير';

        return $this->subject($subject)
                    ->view('emails.donation_success');
    }
}
