<?php

namespace App\Repository\OrderStatusHistory;

use Illuminate\Support\Collection;

interface OrderStatusHistoryRepository
{
    public function forOrder(int $orderId): Collection;

    public function create(array $data);
}
