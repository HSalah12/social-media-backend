<?php

// File: app/Mail/ResetPasswordMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;


class ResetPasswordMail extends Mailable
{


    use Queueable, SerializesModels;



    public $resetToken;



    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($resetToken)
    {
        $this->resetToken = $resetToken;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reset_password')
                ->with(['resetToken' => $this->resetToken]);
    }
}
