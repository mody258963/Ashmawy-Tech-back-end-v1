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

class WorkerHomeServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_progress_home_service_order_and_create_trip_expense(): void
    {
        [$owner, $technician, $order] = $this->homeOrderContext();

        $this->actingAs($technician, 'api');

        $this->patchJson('/api/v1/technician/orders/'.$order->id.'/home-service/start-trip', [
            'trip_expense_amount' => 150,
            'trip_expense_title' => 'Trip spare parts',
        ])->assertOk()
            ->assertJsonPath('home_service_stage', Order::HOME_STAGE_ON_THE_WAY);

        $this->patchJson('/api/v1/technician/orders/'.$order->id.'/home-service/start-service')
            ->assertOk()
            ->assertJsonPath('home_service_stage', Order::HOME_STAGE_IN_PROGRESS);

        $this->patchJson('/api/v1/technician/orders/'.$order->id.'/home-service/mark-done')
            ->assertOk()
            ->assertJsonPath('home_service_stage', Order::HOME_STAGE_DONE)
            ->assertJsonPath('status', 'delivered');

        $this->assertDatabaseHas('expenses', [
            'order_id' => $order->id,
            'created_by' => $technician->id,
            'title' => 'Trip spare parts',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
            'home_service_stage' => Order::HOME_STAGE_DONE,
        ]);

        $this->assertDatabaseCount('order_status_histories', 3);
    }

    public function test_collector_cannot_use_home_service_endpoints(): void
    {
        [, , $order, $collector] = $this->homeOrderContextWithCollector();
        $this->actingAs($collector, 'api');

        $this->patchJson('/api/v1/technician/orders/'.$order->id.'/home-service/start-trip')
            ->assertForbidden();
    }

    public function test_owner_can_override_home_service_actions(): void
    {
        [$owner, , $order] = $this->homeOrderContext();
        $this->actingAs($owner, 'api');

        $this->patchJson('/api/v1/technician/orders/'.$order->id.'/home-service/start-trip')
            ->assertOk()
            ->assertJsonPath('home_service_stage', Order::HOME_STAGE_ON_THE_WAY);
    }

    /**
     * @return array{User, User, Order}
     */
    private function homeOrderContext(): array
    {
        $branch = Branch::query()->create(['name' => 'Main', 'address' => 'A', 'phone' => '0100']);
        $owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner-home@test.local',
            'phone' => '01000000101',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'branch_id' => $branch->id,
        ]);
        $technician = User::query()->create([
            'name' => 'Tech',
            'email' => 'tech-home@test.local',
            'phone' => '01000000102',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'branch_id' => $branch->id,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Customer',
            'phone' => '01000000103',
            'status' => 'new',
            'created_by' => $owner->id,
            'branch_id' => $branch->id,
        ]);
        $device = Device::query()->create([
            'customer_id' => $customer->id,
            'type' => 'Laptop',
            'brand' => 'HP',
            'model' => 'EliteBook',
        ]);
        $order = Order::query()->create([
            'order_number' => 'AW-HOME-001',
            'device_id' => $device->id,
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'branch_id' => $branch->id,
            'estimated_cost' => 500,
            'status' => 'received',
            'service_mode' => Order::SERVICE_MODE_HOME,
            'home_service_stage' => Order::HOME_STAGE_SCHEDULED,
        ]);

        return [$owner, $technician, $order];
    }

    /**
     * @return array{User, User, Order, User}
     */
    private function homeOrderContextWithCollector(): array
    {
        [$owner, $technician, $order] = $this->homeOrderContext();
        $collector = User::query()->create([
            'name' => 'Collector',
            'email' => 'collector-home@test.local',
            'phone' => '01000000104',
            'password' => Hash::make('password'),
            'role' => 'collector',
            'branch_id' => $order->branch_id,
        ]);

        return [$owner, $technician, $order, $collector];
    }
}
