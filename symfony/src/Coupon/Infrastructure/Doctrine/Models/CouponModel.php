<?php

namespace App\Coupon\Infrastructure\Doctrine\Models;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'coupons')]
class CouponModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $code;

    #[ORM\Column(name: 'created_at')]
    public DateTimeImmutable $createdAt;
}
