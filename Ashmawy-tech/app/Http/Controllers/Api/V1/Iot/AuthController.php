<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Iot\IotLoginRequest;
use App\Models\Iot\IotUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(IotLoginRequest $request): JsonResponse
    {
        /** @var IotUser|null $user */
        $user = IotUser::query()->where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => __('Invalid credentials.')], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => __('Account disabled.')], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $tokenName = (string) ($request->validated('device_name') ?? 'iot-mobile-app');
        $token = $user->createToken($tokenName)->accessToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('iot-api')?->token()?->revoke();

        return response()->json(['message' => 'Logged out.']);
    }
}
