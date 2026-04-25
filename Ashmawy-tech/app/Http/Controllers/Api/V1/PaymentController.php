<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user?->role, ['cashier', 'owner', 'moderator'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'in:cash,transfer,card'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $order = Order::query()->findOrFail((int) $data['order_id']);
        if ($user?->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403);
        }

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'received_by' => $user->id,
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        return response()->json($payment, 201);
    }

    public function balance(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($user?->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403);
        }

        if (! in_array($user?->role, ['cashier', 'owner', 'moderator'], true)) {
            abort(403);
        }

        $paid = (float) $order->payments()->sum('amount');
        $total = (float) ($order->final_cost ?? $order->estimated_cost ?? 0);

        return response()->json([
            'order_id' => $order->id,
            'total' => $total,
            'paid' => $paid,
            'remaining' => max($total - $paid, 0),
        ]);
    }
}
