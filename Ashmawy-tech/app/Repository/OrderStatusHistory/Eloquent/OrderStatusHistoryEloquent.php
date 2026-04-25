<?php

namespace App\Repository\OrderStatusHistory\Eloquent;

use App\Models\OrderStatusHistory;
use App\Repository\OrderStatusHistory\OrderStatusHistoryRepository;
use Illuminate\Support\Collection;

class OrderStatusHistoryEloquent implements OrderStatusHistoryRepository
{
    public function __construct(protected OrderStatusHistory $model) {}

    public function forOrder(int $orderId): Collection
    {
        return $this->model->query()
            ->where('order_id', $orderId)
            ->with('changedBy')
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }
}
