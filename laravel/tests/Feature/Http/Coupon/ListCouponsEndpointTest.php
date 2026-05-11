<?php

namespace Tests\Feature\Http\Coupon;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use App\Coupon\Presenter\Http\Coupon\List\ListCouponsController;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see ListCouponsController
 */
class ListCouponsEndpointTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryCouponRepository(
            coupons: [
                Coupon::retrieve(1),
                Coupon::retrieve(2),
            ],
            lastInsertedId: 2,
        );

        $this->swap(CouponRepository::class, $this->repository);
    }

    #[Test]
    public function create_coupon(): void
    {
        CarbonImmutable::setTestNow('2026-05-23T07:20:12+00:00');

        $response = $this->get('/api/coupons');

        $response->assertOk();
        $this->assertEquals(
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
            $response->json(),
        );
    }
}
