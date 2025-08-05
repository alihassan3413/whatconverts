<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->attemptLogin($request->validated());

            if (!$result) {
                return $this->errorResponse(
                    'Invalid credentials',
                    401
                );
            }

            return $this->successResponse(
                [
                    'user' => $result['user'],
                    'token' => $result['token']
                ],
                'Logged in successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while logging in: ' . $e->getMessage(),
                500
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout();
            return $this->successResponse(
                message: 'Logged out successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while logging out: ' . $e->getMessage(),
                500
            );
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getCurrentUser();

            return $this->successResponse(
                ['user' => $user],
                'User retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An error occurred while fetching user data',
                500
            );
        }
    }
}
