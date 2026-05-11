<?php

namespace App\Campaign\Infrastructure\Providers;

use App\Campaign\Presenter\Event\ShareCouponWithCustomersEventListener;
use App\Shared\Domain\Email\EmailSender;
use App\Shared\Domain\Event\Subscriber;
use App\Shared\Infrastructure\Email\ResendEmailSender;
use Illuminate\Support\ServiceProvider;
use Resend;

class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmailSender::class, function ($app) {
            return new ResendEmailSender(
                Resend::client(
                    apiKey: $app->config->get('resend.apiKey'),
                ),
            );
        });
    }

    public function boot(): void
    {
        $this->app->get(Subscriber::class)
            ->subscribe('coupon.created', $this->app->get(ShareCouponWithCustomersEventListener::class));
    }
}
