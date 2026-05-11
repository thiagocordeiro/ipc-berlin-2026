<?php

namespace App\Tests\Feature\Http\Coupon;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use App\Coupon\Presenter\Http\Coupon\List\ListCouponsController;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @see ListCouponsController
 */
class ListCouponsEndpointTest extends WebTestCase
{
    #[Test]
    public function list_coupons(): void
    {
        $client = static::createClient();
        static::getContainer()->set(CouponRepository::class, new InMemoryCouponRepository(
            coupons: [
                Coupon::retrieve(1),
                Coupon::retrieve(2),
            ],
            lastInsertedId: 2,
        ));

        $client->request('GET', '/coupons');

        self::assertResponseIsSuccessful();
        self::assertSame(
            [
                [
                    'id' => 1,
                    'code' => 'IPC-BERLIN-2026',
                    'createdAt' => '2026-05-23T07:20:12+00:00',
                ],
                [
                    'id' => 2,
                    'code' => 'IPC-THIAGO-CORDEIRO',
                    'createdAt' => '2026-05-23T07:20:12+00:00',
                ],
            ],
            json_decode((string) $client->getResponse()->getContent(), true),
        );
    }
}
