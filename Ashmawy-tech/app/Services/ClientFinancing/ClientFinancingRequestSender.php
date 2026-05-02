<?php

namespace App\Services\ClientFinancing;

use App\Mail\ClientFinancingRequestSubmitted;
use Illuminate\Support\Facades\Mail;

final class ClientFinancingRequestSender
{
    /** @return list<string> */
    public function resolveRecipients(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $email): string => trim($email),
            explode(',', (string) config('mail.client_financing_requests_to', ''))
        ), static fn (string $email): bool => $email !== ''));
    }

    /** @param  array<string, mixed>  $payload */
    public function send(array $payload): void
    {
        Mail::to($this->resolveRecipients())->send(new ClientFinancingRequestSubmitted($payload));
    }
}
