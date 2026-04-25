<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Models\WorkerPenalty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WorkerPerformanceService
{
    public function technicianMonthly(CarbonImmutable $monthStart, ?int $branchId = null): Collection
    {
        $monthEnd = $monthStart->endOfMonth();

        $orders = Order::query()
            ->selectRaw('technician_id, COUNT(*) as total_orders')
            ->whereNotNull('technician_id')
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$monthStart, $monthEnd])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('technician_id')
            ->pluck('total_orders', 'technician_id');

        $penalties = WorkerPenalty::query()
            ->selectRaw('user_id, SUM(amount) as total_penalties')
            ->where('applied_for_month', $monthStart->toDateString())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('user_id')
            ->pluck('total_penalties', 'user_id');

        $techIds = $orders->keys()->merge($penalties->keys())->unique()->values();

        return $techIds->map(function ($id) use ($orders, $penalties) {
            return [
                'technician_id' => (int) $id,
                'total_orders' => (int) ($orders[$id] ?? 0),
                'penalties' => (float) ($penalties[$id] ?? 0),
            ];
        });
    }
}
