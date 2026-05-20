<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Models\Iot\IotPushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios'])],
        ]);

        $user = $request->user('iot-api');

        IotPushToken::query()->updateOrCreate(
            [
                'iot_user_id' => $user->id,
                'token' => $validated['token'],
            ],
            ['platform' => $validated['platform']],
        );

        return response()->json(['message' => 'ok']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $user = $request->user('iot-api');

        IotPushToken::query()
            ->where('iot_user_id', $user->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'ok']);
    }
}
