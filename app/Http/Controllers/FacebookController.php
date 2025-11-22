<?php

namespace App\Http\Controllers;

use App\Models\User; // افترض أن لديك نموذج المستخدم
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class FacebookController extends Controller
{
    /**
     * تحويل المستخدم إلى صفحة تسجيل الدخول في فيس بوك
     */
    public function redirectToFacebook()
    {
        // استخدام الصلاحيات المحددة في services.php
        return Socialite::driver('facebook')->scopes(config('services.facebook.scopes'))->redirect();
    }

    /**
     * معالجة الرد القادم من فيس بوك بعد المصادقة
     */
    public function handleFacebookCallback()
    {
        try {
            // 1. الحصول على بيانات المستخدم ورموز الوصول
            $facebookUser = Socialite::driver('facebook')->user();
            $accessToken = $facebookUser->token; // هذا هو رمز الوصول للمستخدم

            // 2. تبادل رمز الوصول القصير برمز وصول طويل الأجل (اختياري لكن موصى به)
            // Socialite يعطي رمز وصول طويل الأجل افتراضياً لمعظم الخدمات.

            // 3. تخزين رمز وصول المستخدم
            $user = User::updateOrCreate([
                'facebook_id' => $facebookUser->id,
            ], [
                'name' => $facebookUser->name,
                'email' => $facebookUser->email,
                'facebook_token' => $accessToken,
            ]);

            Auth::login($user);

            // 4. الانتقال إلى خطوة اختيار الصفحة
            return redirect()->route('facebook.select.page');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'فشل تسجيل الدخول: ' . $e->getMessage());
        }
    }

    /**
     * عرض قائمة الصفحات المتاحة للمستخدم وتخزين رمز وصول الصفحة
     */
    public function selectPage(Request $request)
    {
        // 1. استخدام رمز وصول المستخدم للحصول على قائمة الصفحات
        $user = Auth::user();

        $response = Http::get("https://graph.facebook.com/v24.0/{$user->facebook_id}/accounts", [
            'access_token' => $user->facebook_token,
        ]);

        $pages = $response->json()['data'] ?? [];

        // 2. إذا تم إرسال طلب لاختيار الصفحة
        if ($request->isMethod('post') && $request->has('page_id')) {
            $pageId = $request->page_id;

            // البحث عن الرمز المميز للصفحة المختارة
            $pageAccessToken = collect($pages)->firstWhere('id', $pageId)['access_token'] ?? null;

            if ($pageAccessToken) {
                // 3. تخزين الرمز المميز للصفحة في قاعدة البيانات
                // يجب أن تضيف حقول 'page_id' و 'page_access_token' إلى جدول المستخدم أو جدول مخصص
                $user->update([
                    'selected_page_id' => $pageId,
                    'page_access_token' => $pageAccessToken,
                ]);

                return redirect('/')->with('success', 'تم اختيار الصفحة بنجاح. البوت جاهز للعمل!');
            }
        }

        // عرض صفحة Blade لاختيار الصفحة
        return view('facebook.select-page', compact('pages'));
    }
}
