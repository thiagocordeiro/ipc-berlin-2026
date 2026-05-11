<?php

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Email\EmailSender;
use Override;
use Resend\Contracts\Client as ResendClient;

readonly class ResendEmailSender implements EmailSender
{
    public function __construct(private ResendClient $resend)
    {
    }

    #[Override]
    public function send(string $to, string $subject, string $message): void
    {
        $this->resend->emails->send([
            'from' => 'onboarding@resend.dev',
            'to' => $to,
            'subject' => $subject,
            'html' => $message,
        ]);
    }
}
