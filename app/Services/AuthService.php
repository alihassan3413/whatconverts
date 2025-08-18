<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // Modify your AuthService.php
    public function attemptLogin(array $credentials): array|bool
    {
        if (!Auth::attempt($credentials)) {
            return false;
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    // Remove or modify session methods
    public function regenerateSession(): void
    {
        // Either remove this or make it conditional
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
    }

    public function invalidateSession(): void
    {
        // Either remove this or make it conditional
        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    public function logout(): void
    {
        if (auth()->check()) {
            // For token-based auth: revoke the current token
            auth()->user()->currentAccessToken()->delete();
        }
    }

    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }
}
