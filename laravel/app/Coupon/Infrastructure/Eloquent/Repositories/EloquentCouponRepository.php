<?php

namespace App\Coupon\Infrastructure\Eloquent\Repositories;

use App\Coupon\Domain\Coupon\Coupon;
use App\Coupon\Domain\Coupon\CouponRepository;
use App\Coupon\Infrastructure\Eloquent\Models\CouponModel;
use Exception;

readonly class EloquentCouponRepository implements CouponRepository
{
    public function store(Coupon $coupon): Coupon
    {
        $model = CouponModel::query()->create([
            'code' => $coupon->code,
            'created_at' => $coupon->createdAt,
        ]);

        return $this->modelToEntity($model);
    }

    public function getById(int $id): Coupon
    {
        $model = CouponModel::query()->find($id) ?? throw new Exception('Coupon not found');

        return $this->modelToEntity($model);
    }

    public function getByCode(string $code): Coupon
    {
        $model = CouponModel::query()->where('code', $code)->first() ?? throw new Exception('Coupon not found');

        return $this->modelToEntity($model);
    }

    public function list(): array
    {
        return CouponModel::query()
            ->get()
            ->map($this->modelToEntity(...))
            ->all();
    }

    private function modelToEntity(CouponModel $model): Coupon
    {
        return Coupon::retrieve(
            id: $model->id,
            code: $model->code,
            createdAt: $model->created_at,
        );
    }
}
