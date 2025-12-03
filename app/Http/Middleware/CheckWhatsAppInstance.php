<?php

namespace App\Http\Middleware;

use App\Models\WhatsAppInstance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWhatsAppInstance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $instanceName = $request->route('instanceName');

        if (!$instanceName) {
            return responseFormat('اسم الـ Instance مطلوب', 400);
        }

        $instance = WhatsAppInstance::where('instance_name', $instanceName)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$instance) {
            return responseFormat('الـ Instance غير موجود', 404);
        }

        if (!$instance->is_active) {
            return responseFormat('الـ Instance غير نشط', 403);
        }

        // إضافة الـ instance للـ request
        $request->merge(['whatsapp_instance' => $instance]);

        return $next($request);
    }
}
