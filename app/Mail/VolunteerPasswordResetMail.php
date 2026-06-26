<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VolunteerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $volunteerName;

    public function __construct($code, $volunteerName)
    {
        $this->code = $code;
        $this->volunteerName = $volunteerName;
    }

    public function build()
    {
        return $this->subject('رمز استعادة كلمة المرور | نبضة خير')
                    ->view('emails.volunteer_reset_password');
    }
}
