<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = auth()->login($user);

            DB::commit();

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(array $credentials): ?array
    {
        $token = auth()->attempt($credentials);

        if (!$token) {
            return null;
        }

        return [
            'user' => auth()->user(),
            'token' => $token,
        ];
    }

    public function logout(): void
    {
        auth()->logout();
    }

    public function refresh(): string
    {
        return auth()->refresh();
    }

    public function getCurrentUser(): ?User
    {
        return auth()->user();
    }

    public function updateProfile(User $user, array $data): User
    {
        try {
            DB::beginTransaction();

            $user->update([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
            ]);

            if (isset($data['password'])) {
                $user->update([
                    'password' => Hash::make($data['password'])
                ]);
            }

            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteAccount(User $user): bool
    {
        try {
            DB::beginTransaction();

            // Delete user's orders and related data
            $user->orders()->each(function ($order) {
                $order->items()->delete();
                $order->payments()->delete();
                $order->delete();
            });

            $deleted = $user->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
