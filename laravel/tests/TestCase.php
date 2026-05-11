<?php

namespace Tests;

use App\Shared\Domain\Email\EmailSender;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeEmailSender;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        // The test suite must never reach a real email provider. Bind a spy before any
        // provider boots, so the listener that CampaignServiceProvider subscribes in boot()
        // captures the spy instead of the Resend-backed sender. Fetch it back with
        // `$this->app->get(EmailSender::class)` to assert on what was sent.
        $app->booting(fn (Application $app) => $app->instance(EmailSender::class, new FakeEmailSender()));

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
