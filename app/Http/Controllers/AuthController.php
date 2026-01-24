<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MessageService;
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
            'permissions' => $user->getCurrentSubscription()?->package?->getFeatures() ?? null,
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
            'permissions' => $user->getCurrentSubscription()?->package?->getFeatures() ?? null,
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


    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'captcha' => 'required',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        if (!$this->checkCaptcha($request->captcha, $request->ip())) {
            return responseFormat('Captcha failed', 422);
        }

        // Map type to model

        $modelClass = User::class;

        $user = $modelClass::where('phone', $request->phone)->first();

        if (!$user) {
            return responseFormat('User not found', 404);
        }

        $code = rand(10000, 99999);
        $user->update([
            'otp' => $code,
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $data = MessageService::to($request->phone, "كود التحقق هو $code");
        if ($data) {
            return responseFormat("تم إرسال الكود", 200);
        }
        return responseFormat("فشل إرسال الكود", 500);
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20|exists:users,phone',
            'otp' => 'required|string|max:20',
            'password' => 'required|string|max:20',
        ]);
        // 
        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return responseFormat('User not found', 404);
        }

        if ($user->otp != $request->otp) {
            return responseFormat('Invalid otp', 401);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return responseFormat("تم تغيير كلمة المرور", 200);
    }
}
