<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FoundationPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $foundationName;

    public function __construct($code, $foundationName)
    {
        $this->code = $code;
        $this->foundationName = $foundationName;
    }

    public function build()
    {
        return $this->subject('رمز استعادة كلمة المرور | نبضة خير')
                    ->view('emails.foundation_reset_password'); // سنقوم بإنشاء هذا القالب تالياً
    }
}
