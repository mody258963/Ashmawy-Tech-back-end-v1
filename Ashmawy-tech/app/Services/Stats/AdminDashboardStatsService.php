<?php

namespace App\Services\Stats;

use App\Models\Expense;
use App\Models\FollowUp;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SparePart;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardStatsService
{
    public function dashboard(): array
    {
        $now = CarbonImmutable::now();
        $todayStart = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        return Cache::remember('stats:admin-dashboard:v1', now()->addSeconds(30), function () use ($todayStart, $weekStart, $monthStart, $now) {
            $ordersTotal = Order::query()->count();
            $ordersOpen = Order::query()->whereNotIn('status', ['delivered', 'cancelled'])->count();
            $ordersByStatus = Order::query()
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->orderByDesc('c')
                ->pluck('c', 'status');

            $paymentsToday = Payment::query()->where('paid_at', '>=', $todayStart)->sum('amount');
            $paymentsWeek = Payment::query()->where('paid_at', '>=', $weekStart)->sum('amount');
            $paymentsMonth = Payment::query()->where('paid_at', '>=', $monthStart)->sum('amount');

            $expensesToday = Expense::query()->where('created_at', '>=', $todayStart)->sum('amount');
            $expensesWeek = Expense::query()->where('created_at', '>=', $weekStart)->sum('amount');
            $expensesMonth = Expense::query()->where('created_at', '>=', $monthStart)->sum('amount');

            $inventoryRevenueToday = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $todayStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.selling_price, 0)'));
            $inventoryRevenueWeek = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $weekStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.selling_price, 0)'));
            $inventoryRevenueMonth = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $monthStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.selling_price, 0)'));

            $inventoryCostToday = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $todayStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.cost_price, 0)'));
            $inventoryCostWeek = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $weekStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.cost_price, 0)'));
            $inventoryCostMonth = (float) InventoryMovement::query()
                ->join('spare_parts', 'spare_parts.id', '=', 'inventory_movements.spare_part_id')
                ->where('inventory_movements.movement_type', InventoryMovement::TYPE_SALE)
                ->where('inventory_movements.created_at', '>=', $monthStart)
                ->sum(DB::raw('inventory_movements.quantity * COALESCE(spare_parts.cost_price, 0)'));

            $inventoryProfitToday = $inventoryRevenueToday - $inventoryCostToday;
            $inventoryProfitWeek = $inventoryRevenueWeek - $inventoryCostWeek;
            $inventoryProfitMonth = $inventoryRevenueMonth - $inventoryCostMonth;

            $profitToday = ($paymentsToday - $expensesToday) + $inventoryProfitToday;
            $profitWeek = ($paymentsWeek - $expensesWeek) + $inventoryProfitWeek;
            $profitMonth = ($paymentsMonth - $expensesMonth) + $inventoryProfitMonth;

            $aging3 = Order::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->where('created_at', '<', $now->subDays(3))
                ->count();
            $aging7 = Order::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->where('created_at', '<', $now->subDays(7))
                ->count();
            $aging14 = Order::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->where('created_at', '<', $now->subDays(14))
                ->count();

            $avgDaysToDeliver = (float) Order::query()
                ->whereNotNull('delivered_at')
                ->whereNotNull('received_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, received_at, delivered_at) / 24) as avg_days')
                ->value('avg_days');

            $lowStockCount = SparePart::query()->where('quantity', '<', 5)->count();
            $lowStockList = SparePart::query()
                ->with('branch')
                ->where('quantity', '<', 5)
                ->orderBy('quantity')
                ->limit(10)
                ->get(['id', 'name', 'quantity', 'branch_id']);

            $inventoryToday = InventoryMovement::query()
                ->where('created_at', '>=', $todayStart)
                ->selectRaw('movement_type, COUNT(*) as c, SUM(quantity) as q')
                ->groupBy('movement_type')
                ->get();

            $followUpsDueToday = FollowUp::query()
                ->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$todayStart, $todayStart->endOfDay()])
                ->count();
            $followUpsOverdue = FollowUp::query()
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', $todayStart)
                ->count();

            return [
                'orders' => [
                    'total' => $ordersTotal,
                    'open' => $ordersOpen,
                    'by_status' => $ordersByStatus,
                    'aging' => [
                        'gt_3d' => $aging3,
                        'gt_7d' => $aging7,
                        'gt_14d' => $aging14,
                        'avg_days_to_deliver' => round($avgDaysToDeliver, 2),
                    ],
                ],
                'money' => [
                    'payments' => [
                        'today' => $paymentsToday,
                        'week' => $paymentsWeek,
                        'month' => $paymentsMonth,
                    ],
                    'expenses' => [
                        'today' => $expensesToday,
                        'week' => $expensesWeek,
                        'month' => $expensesMonth,
                    ],
                    'inventory_revenue' => [
                        'today' => $inventoryRevenueToday,
                        'week' => $inventoryRevenueWeek,
                        'month' => $inventoryRevenueMonth,
                    ],
                    'inventory_cost' => [
                        'today' => $inventoryCostToday,
                        'week' => $inventoryCostWeek,
                        'month' => $inventoryCostMonth,
                    ],
                    'inventory_profit' => [
                        'today' => $inventoryProfitToday,
                        'week' => $inventoryProfitWeek,
                        'month' => $inventoryProfitMonth,
                    ],
                    'profit' => [
                        'today' => $profitToday,
                        'week' => $profitWeek,
                        'month' => $profitMonth,
                    ],
                ],
                'inventory' => [
                    'low_stock_count' => $lowStockCount,
                    'low_stock_list' => $lowStockList,
                    'movements_today' => $this->movementSummary($inventoryToday),
                ],
                'followups' => [
                    'due_today' => $followUpsDueToday,
                    'overdue' => $followUpsOverdue,
                ],
            ];
        });
    }

    private function movementSummary(Collection $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[$row->movement_type] = [
                'count' => (int) $row->c,
                'qty' => (int) $row->q,
            ];
        }

        return $out;
    }
}
