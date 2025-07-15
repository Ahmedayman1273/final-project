<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Handle login
    public function login(Request $request)
    {
        try {

            $credentials = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required'],
            ]);

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Unexpected auth error'], 500);
            }


            // Deny web access for student or graduate
            if (
                in_array($user->type, ['student', 'graduate']) &&
                (
                    $this->isWeb($request) ||
                    !$request->expectsJson()
                )
            ) {

                Auth::logout();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied from web'
                ], 403);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token,
                'user'  => [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'personal_email'   => $user->personal_email,
                    'type'             => $user->type,
                    'major'            => $user->major,
                    'phone_number'     => $user->phone_number,
                    'profile_photo'    => $user->profile_photo_path
                        ? asset('storage/' . $user->profile_photo_path)
                        : asset('images/default_avatar.png'),
                    'created_at'       => $user->created_at,
                    'updated_at'       => $user->updated_at,
                ]
            ]);

        } catch (\Throwable $e) {
           
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    // Handle logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    // Check if request is from web
    private function isWeb(Request $request): bool
    {
        return strtolower($request->header('X-From')) === 'web';
    }
}
