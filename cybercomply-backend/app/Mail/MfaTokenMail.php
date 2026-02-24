<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MfaTokenMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $token,
        public readonly CarbonInterface $expiresAt
    ) {
    }

    public function build(): self
    {
        return $this->subject('CyberComply - Código de verificação')
            ->view('emails.mfa-token', [
                'token' => $this->token,
                'minutes' => 10,
            ]);
    }
}

