<?php

namespace Tests\Feature\Campaign\Http;

use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see ShareCouponWithCustomersController
 */
class ShareCouponWithCustomersEndpointTest extends TestCase
{
    #[Test]
    public function given_x_when_x_then(): void
    {
        $response = $this->post('/api/share/IPC-BERLIN-2026');

        $this->assertSame(200, $response->status());
    }
}
