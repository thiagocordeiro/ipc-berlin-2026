<?php

namespace Tests\Feature\Http\Coupon;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use App\Coupon\Presenter\Http\Coupon\Get\GetCouponController;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see GetCouponController
 */
class GetCouponEndpointTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryCouponRepository(
            coupons: [
                Coupon::retrieve(
                    id: 1,
                    code: 'IPC-BERLIN-2026',
                    createdAt: CarbonImmutable::parse('2026-05-23T07:20:12+00:00'),
                ),
                Coupon::retrieve(
                    id: 2,
                    code: 'IPC-THIAGO-CORDEIRO',
                    createdAt: CarbonImmutable::parse('2026-05-23T07:20:12+00:00'),
                ),
            ],
            lastInsertedId: 2,
        );

        $this->swap(CouponRepository::class, $this->repository);
    }

    #[Test]
    public function get_coupon_by_code(): void
    {
        $response = $this->get('/api/coupons/IPC-BERLIN-2026');

        $response->assertOk();
        $this->assertEquals(
            [
                'id' => 1,
                'code' => 'IPC-BERLIN-2026',
                'createdAt' => '2026-05-23T07:20:12+00:00',
            ],
            $response->json(),
        );
    }
}
