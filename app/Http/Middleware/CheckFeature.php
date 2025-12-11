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
    public function handle(Request $request, Closure $next, ?string $feature = null, ?string $resource = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return responseFormat('غير مصرح', 401);
        }

        // التحقق من وجود اشتراك نشط
        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة', 403);
        }

        // التحقق من الميزة
        if ($feature && !$user->hasFeature($feature)) {
            return responseFormat('هذه الميزة غير متاحة في باقتك الحالية. يرجى الترقية للباقة الأعلى', 403);
        }

        // التحقق من الحد (الموارد)
        if ($resource && !$user->canAdd($resource)) {
            return responseFormat('لقد تجاوزت الحد المسموح به في باقتك الحالية. يرجى الترقية لإضافة المزيد', 403);
        }

        return $next($request);
    }
}
