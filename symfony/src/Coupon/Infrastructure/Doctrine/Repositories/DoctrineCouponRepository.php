<?php

namespace App\Coupon\Infrastructure\Doctrine\Repositories;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Doctrine\Models\CouponModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class DoctrineCouponRepository implements CouponRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function store(Coupon $coupon): Coupon
    {
        $model = new CouponModel();
        $model->code = $coupon->code;
        $model->createdAt = $coupon->createdAt;

        $this->entityManager->persist($model);
        $this->entityManager->flush();

        return $this->modelToEntity($model);
    }

    public function getById(int $id): Coupon
    {
        $model = $this->entityManager->find(CouponModel::class, $id) ?? throw new Exception('Coupon not found');

        return $this->modelToEntity($model);
    }

    public function list(): array
    {
        return [];
    }

    private function modelToEntity(CouponModel $model): Coupon
    {
        return Coupon::retrieve(
            id: $model->id,
            code: $model->code,
            createdAt: $model->createdAt,
        );
    }
}
