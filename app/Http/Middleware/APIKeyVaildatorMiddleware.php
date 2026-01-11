<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class APIKeyVaildatorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->bearerToken();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)->where('is_active', true)->first();

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key'
            ], 401);
        }
        $key->update([
            'last_used' => now()
        ]);
        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key'
            ], 401);
        }
        $instance = $key->whatsappInstance;
        $user = $instance->user;
        if (!$user) {
            return responseFormat('المستخدم غير موجود', 401);
        }

        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة', 403);
        }
        if (!$user->hasFeature('whatsapp_developer') || !$user->hasFeature('whatsapp')) {
            return responseFormat('هذه الميزة غير متاحة في باقتك الحالية. يرجى الترقية للباقة الأعلى', 403);
        }


        $request->merge(['api_key' => $key, 'instance' => $instance]);

        return $next($request);
    }
}
