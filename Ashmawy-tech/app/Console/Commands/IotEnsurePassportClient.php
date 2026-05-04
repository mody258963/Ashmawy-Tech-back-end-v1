<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class IotEnsurePassportClient extends Command
{
    protected $signature = 'iot:ensure-passport-client';

    protected $description = 'Create a Passport personal access client for the iot_users provider if missing.';

    public function handle(ClientRepository $clients): int
    {
        $exists = Client::query()
            ->where('revoked', false)
            ->where('provider', 'iot_users')
            ->get()
            ->contains(fn (Client $c): bool => $c->hasGrantType('personal_access'));

        if ($exists) {
            $this->info('IoT Passport personal access client already exists.');

            return self::SUCCESS;
        }

        $clients->createPersonalAccessGrantClient('IoT Personal Access Client', 'iot_users');
        $this->info('Created IoT Passport personal access client for provider iot_users.');

        return self::SUCCESS;
    }
}
