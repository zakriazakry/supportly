<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح',
                'data' => null
            ], 401);
        }

        // التحقق من وجود اشتراك نشط
        if (!$user->hasActiveSubscription()) {
            return response()->json([
                'status' => false,
                'message' => 'يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة',
                'data' => null
            ], 403);
        }

        // التحقق من الميزة
        if (!$user->hasFeature($feature)) {
            return response()->json([
                'status' => false,
                'message' => 'هذه الميزة غير متاحة في باقتك الحالية. يرجى الترقية للباقة الأعلى',
                'data' => [
                    'required_feature' => $feature,
                    'current_package' => $user->getCurrentSubscription()?->package?->name
                ]
            ], 403);
        }

        return $next($request);
    }
}
