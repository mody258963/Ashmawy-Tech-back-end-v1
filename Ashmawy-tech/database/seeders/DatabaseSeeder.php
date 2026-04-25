<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['name' => 'Main branch'],
            ['address' => null, 'phone' => null],
        );

        User::query()->updateOrCreate(
            ['email' => 'owner@ashmawy.test'],
            [
                'name' => 'Owner',
                'phone' => '01000000001',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'branch_id' => $branch->id,
            ],
        );
    }
}
