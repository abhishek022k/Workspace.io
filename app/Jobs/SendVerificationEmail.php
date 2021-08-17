<?php

namespace App\Jobs;

use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmail extends Job 
{
    protected $email,$user,$unique;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$user,$unique)
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
        $email = new VerificationMail($this->user, $this->unique);
        Mail::to($this->email)->send($email);
    }
}
