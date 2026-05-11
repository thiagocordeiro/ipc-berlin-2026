<?php

namespace Tests\Feature\Http\Coupon;

use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Memory\InMemoryCouponRepository;
use App\Coupon\Presenter\Http\Coupon\Create\CreateCouponController;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see CreateCouponController
 */
class CreateCouponEndpointTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryCouponRepository();
        $this->swap(CouponRepository::class, $this->repository);
    }

    #[Test]
    public function create_coupon(): void
    {
        CarbonImmutable::setTestNow('2026-05-23T07:20:12+00:00');

        $response = $this->post('/api/coupons', [
            'code' => 'BERLIN-2026',
        ]);

        $response->assertCreated();

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
