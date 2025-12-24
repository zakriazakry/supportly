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
        $request->merge(['api_key' => $key, 'instance' => $instance]);

        return $next($request);
    }
}
