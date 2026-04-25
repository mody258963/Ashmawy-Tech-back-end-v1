<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\SparePart;
use App\Services\Inventory\SparePartStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OrderSparePartController
{
    public function store(Request $request, Order $order, SparePartStockService $stock): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user?->role, ['technician', 'owner', 'moderator'], true)) {
            abort(403);
        }
        if ($user?->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            abort(403);
        }
        if ($user?->role === 'technician' && (int) $order->technician_id !== (int) $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'spare_part_id' => ['required', 'exists:spare_parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $part = SparePart::query()->findOrFail((int) $data['spare_part_id']);
        if ((int) $part->branch_id !== (int) $order->branch_id) {
            abort(422, 'Spare part must belong to same branch as order.');
        }

        $unitPrice = $data['unit_price'] ?? $part->selling_price;

        $order->spareParts()->attach($part->id, [
            'quantity' => (int) $data['quantity'],
            'unit_price' => $unitPrice ?? 0,
        ]);

        try {
            $stock->recordSale(
                $part,
                (int) $data['quantity'],
                $unitPrice !== null ? (string) $unitPrice : null,
                $user->id,
                'Consumed on order '.$order->order_number,
                Order::class,
                $order->id,
            );
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return response()->json([
            'order_id' => $order->id,
            'spare_part_id' => $part->id,
            'quantity' => (int) $data['quantity'],
            'unit_price' => $unitPrice,
        ], 201);
    }
}
