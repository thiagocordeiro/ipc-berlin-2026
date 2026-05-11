<?php

namespace App\Tests\Feature\Http\Coupon;

use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateCouponEndpointTest extends WebTestCase
{
    #[Test]
    public function create_coupon(): void
    {
        $client = static::createClient();
        static::getContainer()->set(CouponRepository::class, new InMemoryCouponRepository());

        $client->request(
            method: 'POST',
            uri: '/coupons',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['code' => 'BERLIN-2026']),
        );

        self::assertResponseStatusCodeSame(201);
        self::assertSame(
            [
                'id' => 1,
                'code' => 'IPC-BERLIN-2026',
                'createdAt' => '2026-05-23T07:20:12+00:00',
            ],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }
}
