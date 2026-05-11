<?php

namespace App\Tests\Support;

use App\Shared\Domain\Email\EmailSender;
use Override;

/**
 * Test double for {@see EmailSender}: records every send instead of hitting a provider.
 */
class FakeEmailSender implements EmailSender
{
    /** @var list<array{to: string, subject: string, message: string}> */
    public array $sent = [];

    #[Override]
    public function send(string $to, string $subject, string $message): void
    {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'message' => $message];
    }
}
