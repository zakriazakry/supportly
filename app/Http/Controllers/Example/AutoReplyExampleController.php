<?php

namespace App\Http\Controllers\Example;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * هذا مثال توضيحي لكيفية استخدام القيود الشهرية
 * يمكنك تطبيق نفس المنطق في FacebookWebhookController أو أي controller آخر
 */
class AutoReplyExampleController extends Controller
{
    /**
     * مثال: إرسال رد تلقائي مع التحقق من القيود الشهرية
     */
    public function sendAutoReply(Request $request)
    {
        $user = $request->user();

        // 1. التحقق من وجود اشتراك نشط
        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط لاستخدام الردود التلقائية', 403);
        }

        // 2. التحقق من القيود الشهرية
        if (!$user->canSendAutoReply()) {
            $remaining = $user->getRemainingAutoReplies();
            $limit = $user->getLimit('auto_replies_per_month');

            return responseFormat([
                'message' => 'لقد وصلت للحد الأقصى من الردود التلقائية لهذا الشهر',
                'limit' => $limit,
                'remaining' => 0,
                'upgrade_message' => 'قم بالترقية للباقة الاحترافية للحصول على ردود غير محدودة',
                'upgrade_required' => true
            ], 403);
        }

        // 3. إرسال الرد التلقائي
        // ... منطق إرسال الرد ...

        // 4. تحديث العداد
        $user->incrementAutoReplyCount();

        // 5. إرجاع النتيجة مع المعلومات
        return responseFormat([
            'message' => 'تم إرسال الرد بنجاح',
            'remaining_replies' => $user->getRemainingAutoReplies(),
        ]);
    }

    /**
     * مثال: الحصول على إحصائيات الاستخدام الشهري
     */
    public function getMonthlyUsage(Request $request)
    {
        $user = $request->user();

        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
        }

        $currentUsage = $user->getCurrentMonthUsage();
        $limit = $user->getLimit('auto_replies_per_month');
        $hasUnlimited = $user->hasFeature('unlimited_replies');

        $data = [
            'current_month' => [
                'year' => $currentUsage->year,
                'month' => $currentUsage->month,
                'month_name' => now()->month($currentUsage->month)->locale('ar')->monthName,
            ],
            'usage' => [
                'auto_replies' => [
                    'used' => $currentUsage->auto_replies_count,
                    'limit' => $hasUnlimited ? 'غير محدود' : $limit,
                    'remaining' => $hasUnlimited ? 'غير محدود' : $user->getRemainingAutoReplies(),
                    'percentage' => $hasUnlimited ? 0 : ($limit > 0 ? round(($currentUsage->auto_replies_count / $limit) * 100, 2) : 0),
                ],
                'messages_sent' => $currentUsage->messages_sent,
                'comments_replied' => $currentUsage->comments_replied,
            ],
            'package_info' => [
                'name' => $user->getCurrentSubscription()?->package?->name,
                'has_unlimited_replies' => $hasUnlimited,
            ]
        ];

        return responseFormat($data);
    }

    /**
     * مثال: الحصول على إحصائيات آخر 6 أشهر
     */
    public function getUsageHistory(Request $request)
    {
        $user = $request->user();

        if (!$user->hasActiveSubscription()) {
            return responseFormat('يجب أن يكون لديك اشتراك نشط', 403);
        }

        // التحقق من ميزة التقارير المتقدمة
        if (!$user->hasFeature('advanced_reports')) {
            return responseFormat([
                'message' => 'التقارير التاريخية متاحة فقط في الباقة الاحترافية',
                'upgrade_required' => true
            ], 403);
        }

        $stats = $user->monthlyUsageStats()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($stat) {
                return [
                    'period' => "{$stat->year}-{$stat->month}",
                    'month_name' => now()->month($stat->month)->locale('ar')->monthName,
                    'year' => $stat->year,
                    'auto_replies' => $stat->auto_replies_count,
                    'messages_sent' => $stat->messages_sent,
                    'comments_replied' => $stat->comments_replied,
                ];
            });

        return responseFormat($stats);
    }
}
