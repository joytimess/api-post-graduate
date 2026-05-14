<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/auth/login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Hapus token lama supaya tidak numpuk
        $user->tokens()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addHours(8) // token expired 8 jam
        )->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token'      => $token,
            'token_type' => 'Bearer',
            'expired_at' => now()->addHours(8)->toDateTimeString(),
        ]);
    }

    // POST /api/auth/logout
    public function logout(Request $request): JsonResponse
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    // GET /api/auth/me
    // Cek user yang sedang login
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    // POST /api/auth/refresh
    // Refresh token — hapus yang lama, buat yang baru
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Hapus token lama
        $user->currentAccessToken()->delete();

        // Buat token baru
        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addHours(8)
        )->plainTextToken;

        return response()->json([
            'message'    => 'Token berhasil diperbarui.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'expired_at' => now()->addHours(8)->toDateTimeString(),
        ]);
    }
}