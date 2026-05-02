<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreClientFinancingRequest;
use App\Mail\ClientFinancingRequestSubmitted;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ClientFinancingRequestController
{
    public function store(StoreClientFinancingRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $recipients = array_values(array_filter(array_map(
            static fn ($email): string => trim((string) $email),
            explode(',', (string) config('mail.client_financing_requests_to', ''))
        )));

        if ($recipients === []) {
            Log::error('Client financing recipient email is not configured.');

            return response()->json([
                'message' => 'Recipient email is not configured.',
            ], 500);
        }

        try {
            Mail::to($recipients)->send(new ClientFinancingRequestSubmitted($payload));
        } catch (Throwable $e) {
            Log::error('Failed to send client financing request email.', [
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send request now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => 'Request submitted successfully.',
        ], 201);
    }
}

