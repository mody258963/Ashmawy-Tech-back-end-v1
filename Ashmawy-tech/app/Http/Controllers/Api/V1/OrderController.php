<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::query()
            ->with(['customer', 'device', 'branch'])
            ->orderByDesc('id');

        if ($user?->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        if (in_array($user?->role, ['technician', 'collector'], true)) {
            $query->where(function ($q) use ($user) {
                $q->where('technician_id', $user->id)->orWhere('collector_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $order->load([
            'customer',
            'device',
            'branch',
            'technician:id,name,role,branch_id',
            'collector:id,name,role,branch_id',
            'payments',
            'spareParts',
            'notes.user',
            'statusHistories.changedBy',
        ]);

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        $user = $request->user();

        if (in_array($user?->role, ['technician', 'collector'], true)) {
            return response()->json([
                'message' => 'Use role-specific workflow endpoints for status changes.',
            ], 403);
        }

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['pending_pickup', 'received', 'diagnosing', 'waiting_approval', 'repairing', 'ready', 'delivered', 'cancelled']),
            ],
        ]);

        $from = $order->status;
        $to = $data['status'];

        if ($from !== $to) {
            $payload = ['status' => $to];
            if ($to === 'received' && $order->received_at === null) {
                $payload['received_at'] = now();
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
        }

        return response()->json($order->fresh());
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user?->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403);
        }

        if (in_array($user?->role, ['owner', 'moderator', 'cashier'], true)) {
            return;
        }

        if ($user?->role === 'technician' && (int) $order->technician_id === (int) $user->id) {
            return;
        }

        if ($user?->role === 'collector' && (int) $order->collector_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
