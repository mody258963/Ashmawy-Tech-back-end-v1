<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerOrderFlowController
{
    public function collectorPickupFromCustomer(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['collector', 'owner'], true)) {
            abort(403, 'Only collector can pick up from customer.');
        }

        if ($user?->role !== 'owner' && (int) $order->collector_id !== (int) $user->id) {
            abort(403, 'Order not assigned to this collector.');
        }

        $this->assertSameBranch($request, $order);
        $this->ensureCurrentStatus($order, ['pending_pickup']);

        return $this->changeStatus($request, $order, 'received');
    }

    public function technicianFinishFixing(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['technician', 'owner'], true)) {
            abort(403, 'Only technician can finish fixing.');
        }

        if ($user?->role !== 'owner' && (int) $order->technician_id !== (int) $user->id) {
            abort(403, 'Order not assigned to this technician.');
        }

        $this->assertSameBranch($request, $order);
        $this->ensureCurrentStatus($order, ['received', 'diagnosing', 'waiting_approval', 'repairing']);

        return $this->changeStatus($request, $order, 'ready');
    }

    public function collectorPendingDelivery(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['collector', 'owner'], true)) {
            abort(403, 'Only collector can access pending delivery list.');
        }

        $query = Order::query()
            ->with(['customer:id,name,phone', 'device:id,type,brand,model', 'branch:id,name'])
            ->where('status', 'ready')
            ->orderBy('updated_at');

        if ($user?->role !== 'owner') {
            $query->where('collector_id', $user->id);
        }

        if ($user?->branch_id && $user?->role !== 'owner') {
            $query->where('branch_id', $user->branch_id);
        }

        $orders = $query->get()->map(function (Order $order): array {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'workflow_status' => 'pending_delivery',
                'customer' => $order->customer,
                'device' => $order->device,
                'branch' => $order->branch,
                'final_cost' => $order->final_cost,
                'received_at' => $order->received_at,
                'delivered_at' => $order->delivered_at,
            ];
        });

        return response()->json([
            'data' => $orders,
            'count' => $orders->count(),
        ]);
    }

    public function collectorMarkDelivered(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['collector', 'owner'], true)) {
            abort(403, 'Only collector can mark delivered.');
        }

        if ($user?->role !== 'owner' && (int) $order->collector_id !== (int) $user->id) {
            abort(403, 'Order not assigned to this collector.');
        }

        $this->assertSameBranch($request, $order);
        $this->ensureCurrentStatus($order, ['ready']);

        return $this->changeStatus($request, $order, 'delivered');
    }

    private function assertSameBranch(Request $request, Order $order): void
    {
        $user = $request->user();
        if ($user?->role === 'owner') {
            return;
        }
        if ($user?->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403, 'Order outside user branch.');
        }
    }

    /**
     * @param array<int, string> $allowedStatuses
     */
    private function ensureCurrentStatus(Order $order, array $allowedStatuses): void
    {
        if (! in_array((string) $order->status, $allowedStatuses, true)) {
            abort(422, 'Invalid order status transition.');
        }
    }

    private function changeStatus(Request $request, Order $order, string $to): JsonResponse
    {
        $from = (string) $order->status;
        if ($from === $to) {
            return response()->json($order->fresh());
        }

        $payload = ['status' => $to];
        if ($to === 'received' && $order->received_at === null) {
            $payload['received_at'] = now();
        }
        if ($to === 'ready') {
            $payload['delivered_at'] = null;
        }
        if ($to === 'delivered' && $order->delivered_at === null) {
            $payload['delivered_at'] = now();
        }

        $order->update($payload);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        return response()->json($order->fresh(['customer', 'device', 'branch']));
    }
}

