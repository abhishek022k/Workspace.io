<?php

namespace App\Jobs;

use App\Mail\PasswordReset;
use Illuminate\Support\Facades\Mail;
class SendPasswordResetMail extends Job
{
    protected $email,$user,$unique;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$unique,$user)
    {
        $this->email = $email;
        $this->user = $user;
        $this->unique = $unique;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new PasswordReset($this->unique,$this->user);
        Mail::to($this->email)->send($email);
    }
}
