<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_appointment_and_validation_is_enforced(): void
    {
        [$admin, $customer, $technician] = $this->baseContext();
        $this->actingAs($admin, 'api');

        $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
        ])->assertStatus(422);

        $this->postJson('/api/v1/appointments', [
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'address' => 'Test address',
            'address_link' => 'https://maps.google.com/?q=30,31',
            'notes' => 'Customer available after 5 PM',
        ])->assertCreated();

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_technician_only_sees_assigned_appointments(): void
    {
        [$admin, $customer, $technician] = $this->baseContext();
        $otherTechnician = User::query()->create([
            'name' => 'Tech 2',
            'email' => 'tech2@test.local',
            'phone' => '01000000004',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'branch_id' => $admin->branch_id,
        ]);

        $mine = Appointment::query()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'scheduled_at' => now()->addDay(),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        Appointment::query()->create([
            'customer_id' => $customer->id,
            'technician_id' => $otherTechnician->id,
            'scheduled_at' => now()->addDays(2),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->actingAs($technician, 'api');
        $response = $this->getJson('/api/v1/appointments')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_non_authorized_role_cannot_update_appointment_status(): void
    {
        [$admin, $customer, $technician] = $this->baseContext();
        $collector = User::query()->create([
            'name' => 'Collector 1',
            'email' => 'collector@test.local',
            'phone' => '01000000005',
            'password' => Hash::make('password'),
            'role' => 'collector',
            'branch_id' => $admin->branch_id,
        ]);

        $appointment = Appointment::query()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'scheduled_at' => now()->addDay(),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        $this->actingAs($collector, 'api');
        $this->patchJson('/api/v1/appointments/'.$appointment->id.'/status', [
            'status' => Appointment::STATUS_DONE,
        ])->assertForbidden();
    }

    private function baseContext(): array
    {
        $branch = Branch::query()->create(['name' => 'Main', 'address' => 'A', 'phone' => '0100']);
        $admin = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner-appointment@test.local',
            'phone' => '01000000001',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'branch_id' => $branch->id,
        ]);
        $technician = User::query()->create([
            'name' => 'Tech 1',
            'email' => 'tech1@test.local',
            'phone' => '01000000002',
            'password' => Hash::make('password'),
            'role' => 'technician',
            'branch_id' => $branch->id,
        ]);
        $customer = Customer::query()->create([
            'name' => 'Customer A',
            'phone' => '01000000003',
            'status' => 'new',
            'created_by' => $admin->id,
            'branch_id' => $branch->id,
        ]);

        return [$admin, $customer, $technician];
    }
}

