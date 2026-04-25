<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = (string) ($user?->role ?? '');

        $appHome = match ($role) {
            'owner' => 'owner.dashboard',
            'collector' => 'collector.pickup_orders',
            'technician' => 'technician.my_orders',
            default => 'unsupported',
        };

        $allowedFlows = match ($role) {
            'owner' => [
                'orders_read',
                'orders_status_update',
                'appointments_manage',
                'payments_manage',
            ],
            'collector' => ['pickup_from_customer', 'pending_delivery', 'mark_delivered'],
            'technician' => ['finish_fixing', 'order_status_read'],
            default => [],
        };

        return response()->json([
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'role' => $user?->role,
            'branch_id' => $user?->branch_id,
            'app_home' => $appHome,
            'allowed_flows' => $allowedFlows,
        ]);
    }
}
