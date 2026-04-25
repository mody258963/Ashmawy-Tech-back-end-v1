<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationsRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_pickup_requires_received_at(): void
    {
        [$user, $customer, $device] = $this->baseOrderContext();

        $response = $this->actingAs($user)->post(route('admin.orders.store'), [
            'device_id' => $device->id,
            'customer_id' => $customer->id,
            'estimated_cost' => 100,
            'status' => 'pending_pickup',
        ]);

        $response->assertSessionHasErrors('received_at');
    }

    public function test_delivered_order_auto_creates_payment_for_remaining_amount(): void
    {
        [$user, $customer, $device] = $this->baseOrderContext();
        $order = Order::query()->create([
            'order_number' => 'AW-TEST-1',
            'device_id' => $device->id,
            'customer_id' => $customer->id,
            'estimated_cost' => 200,
            'final_cost' => 300,
            'status' => 'received',
            'approved' => false,
            'branch_id' => $customer->branch_id,
        ]);

        $this->actingAs($user)->put(route('admin.orders.update', $order), [
            'device_id' => $device->id,
            'customer_id' => $customer->id,
            'estimated_cost' => 200,
            'final_cost' => 300,
            'status' => 'delivered',
            'received_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'delivered_at' => now()->format('Y-m-d H:i:s'),
            'approved' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 300,
            'received_by' => $user->id,
        ]);
    }

    private function baseOrderContext(): array
    {
        $branch = Branch::query()->create(['name' => 'Main', 'address' => 'A', 'phone' => '0100']);
        $user = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'phone' => '01000000022',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'branch_id' => $branch->id,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Test Customer',
            'phone' => '01000009999',
            'status' => 'new',
            'created_by' => $user->id,
            'branch_id' => $branch->id,
        ]);
        $device = Device::query()->create([
            'customer_id' => $customer->id,
            'type' => 'Phone',
        ]);

        return [$user, $customer, $device];
    }
}
