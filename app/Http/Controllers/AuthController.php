<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        if (!$this->checkCaptcha($request->captcha, $request->ip())) {
            return responseFormat('Captcha failed', 422);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return responseFormat('User not found', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return responseFormat('Invalid password', 401);
        }
        $token = $user->createToken('auth-token')->plainTextToken;
        return responseFormat([
            'token' => $token,
            'user' => $user,
            'permissions' => $user->getCurrentSubscription(),
        ], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required',
            'captcha' => 'required',
        ]);
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }
        if (!$this->checkCaptcha($request->captcha, $request->ip())) {
            return responseFormat('Captcha failed', 422);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;
        return responseFormat([
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    private function checkCaptcha($captcha, $ip): bool
    {
        $response = Http::asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => env('CLOUDFLARE_SECRET_KEY'),
                'response' => $captcha,
                'remoteip' => $ip,
            ]
        );

        if (!($response->json()['success'] ?? false)) {
            return false;
        }
        return true;
    }
}
