<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function handleGoogleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string', // هذا هو الـ Access Token القادم من جوجل عبر الفرونت إند
        ]);

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(), // رابط صورة حساب جوجل
                    'password'  => Hash::make(uniqid() . Str::random(12)),
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->getId()
                ]);
            }

            $token = $user->createToken('UserAccess')->plainTextToken;
            $user->avatar_url = filter_var($user->avatar, FILTER_VALIDATE_URL) ? $user->avatar : ($user->avatar ? asset('storage/' . $user->avatar) : null);
            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error("API Google Login Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة تسجيل الدخول عبر جوجل. يرجى التأكد من صلاحية التوكن.'
            ], 401, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
