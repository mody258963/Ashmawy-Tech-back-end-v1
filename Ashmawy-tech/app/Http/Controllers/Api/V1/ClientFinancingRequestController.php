<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreClientFinancingRequest;
use App\Services\ClientFinancing\ClientFinancingRequestSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClientFinancingRequestController
{
    /**
     * Browsers issue GET requests; submissions must use POST. This avoids a raw 405
     * when someone opens the API URL directly and documents the correct usage.
     */
    public function usage(): JsonResponse
    {
        return response()->json([
            'message' => 'Submit this form with HTTP POST (JSON). Opening this URL in a browser alone only shows this info.',
            'method' => 'POST',
            'path' => '/api/v1/client-financing-requests',
            'body' => [
                'name' => 'string (required)',
                'phone' => 'string (required)',
                'car_type' => 'string (required)',
                'car_price' => 'number (required)',
                'down_payment' => 'number (required)',
                'income_proofs' => 'string[] — at least one, each must be exactly one of the allowed options',
            ],
            'income_proof_options' => StoreClientFinancingRequest::INCOME_PROOF_OPTIONS,
            'example' => [
                'name' => 'Ahmed Ali',
                'phone' => '01000000000',
                'car_type' => 'Toyota Corolla',
                'car_price' => 650000,
                'down_payment' => 150000,
                'income_proofs' => ['كشف حساب ٦ شهور', 'حيازه ارض زراعيه'],
            ],
            'web_form_url' => url('/client-financing'),
        ]);
    }

    public function store(StoreClientFinancingRequest $request, ClientFinancingRequestSender $sender): JsonResponse
    {
        $payload = $request->validated();

        if ($sender->resolveRecipients() === []) {
            Log::error('Client financing recipient email is not configured.');

            return response()->json([
                'message' => 'Recipient email is not configured.',
            ], 500);
        }

        try {
            $sender->send($payload);
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

