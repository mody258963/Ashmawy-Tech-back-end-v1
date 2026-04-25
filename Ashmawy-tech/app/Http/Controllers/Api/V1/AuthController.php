<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $login = (string) $data['login'];
        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (! in_array($user->role, ['owner', 'collector', 'technician'], true)) {
            return response()->json([
                'message' => 'This app is only for owner, collector, and technician roles.',
            ], 403);
        }

        $tokenName = (string) ($data['device_name'] ?? 'worker-mobile-app');
        $token = $user->createToken($tokenName)->accessToken;

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'branch_id' => $user->branch_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->token()?->revoke();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}

