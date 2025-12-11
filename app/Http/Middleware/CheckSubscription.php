<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return responseFormat('غير مصرح', 401);
        }

        // التحقق من وجود اشتراك نشط
        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط للوصول إلى هذه الميزة', 403);
        }

        return $next($request);
    }
}
