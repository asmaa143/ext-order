<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {

    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());

            return $this->created([
                'user' => $result['user'],
                'authorization' => [
                    'token' => $result['token'],
                    'type' => 'bearer',
                ]
            ], 'User registered successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return $this->unauthorized('Invalid credentials');
        }

        return $this->success([
            'user' => $result['user'],
            'authorization' => [
                'token' => $result['token'],
                'type' => 'bearer',
            ]
        ], 'Login successful');
    }

    public function logout()
    {
        $this->authService->logout();
        return $this->success(null, 'Successfully logged out');
    }

    public function refresh()
    {
        $token = $this->authService->refresh();

        return $this->success([
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ], 'Token refreshed successfully');
    }

    public function me()
    {
        $user = $this->authService->getCurrentUser();
        return $this->success($user);
    }
}
