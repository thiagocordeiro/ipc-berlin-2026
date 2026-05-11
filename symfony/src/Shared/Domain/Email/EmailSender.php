<?php

namespace App\Shared\Domain\Email;

interface EmailSender
{
    public function send(string $to, string $subject, string $message): void;
}
