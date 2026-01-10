<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MonthlyUsageStat;
use App\Models\WhatsAppMessage;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $subscription = $user->getCurrentSubscription();
        $package = $subscription?->package;
        $wallet = $user->getActiveWallet();
        $monthlyStats = $user->getCurrentMonthUsage();

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
                'member_since_days' => intval(Carbon::parse($user->created_at)->diffInDays(now())),
            ],
            'subscription' => $this->getSubscriptionData($subscription, $package),
            'wallet' => $this->getWalletData($wallet, $user),
            'statistics' => $this->getStatistics($user, $package, $monthlyStats),
            'features' => $this->getFeatures($package),
            'limits' => $this->getLimitsUsage($user, $package),
            'quick_stats' => $this->getQuickStats($user),
            'time_based_stats' => $this->getTimeBasedStats($user),
            'recent_activity' => $this->getRecentActivity($user),
        ];

        return responseFormat($data);
    }
    public function getPermissions(Request $request)
    {
        $user = $request->user();
        return responseFormat($user->getCurrentSubscription()?->package?->getFeatures() ?? null);
    }

    private function getSubscriptionData($subscription, $package): array
    {
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'message' => 'لا يوجد اشتراك نشط',
            ];
        }

        $daysRemaining = Carbon::parse($subscription->end_date)->diffInDays(now(), false);
        $totalDays = Carbon::parse($subscription->start_date)->diffInDays($subscription->end_date);
        $usedDays = Carbon::parse($subscription->start_date)->diffInDays(now());
        $progressPercentage = $totalDays > 0 ? round(($usedDays / $totalDays) * 100) : 0;

        return [
            'has_subscription' => true,
            'package_id' => $package->id,
            'package_name' => $package->name,
            'package_description' => $package->description,
            'package_price' => $package->price,
            'currency' => $package->currency,
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'days_remaining' => abs($daysRemaining),
            'days_used' => $usedDays,
            'total_days' => $totalDays,
            'progress_percentage' => min($progressPercentage, 100),
            'is_expiring_soon' => $daysRemaining <= 7 && $daysRemaining > 0,
            'is_expired' => $daysRemaining < 0,
            'auto_renew' => $subscription->auto_renew,
            'paid_amount' => $subscription->paid_amount,
            'payment_method' => $subscription->payment_method,
        ];
    }

    private function getWalletData($wallet, $user): array
    {
        if (!$wallet) {
            return [
                'has_wallet' => false,
                'balance' => 0,
            ];
        }

        $recentTransactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'description' => $t->description,
                'created_at' => $t->created_at,
            ]);

        $thisMonthCredits = $wallet->transactions()
            ->where('type', 'credit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $thisMonthDebits = $wallet->transactions()
            ->where('type', 'debit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return [
            'has_wallet' => true,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
            'this_month_credits' => $thisMonthCredits,
            'this_month_debits' => $thisMonthDebits,
            'total_transactions' => $wallet->transactions()->count(),
            'recent_transactions' => $recentTransactions,
        ];
    }



    private function getStatistics($user, $package, $monthlyStats): array
    {
        $stats = [];

        if ($package?->feature_facebook) {
            $activePosts = $user->posts()->where('enabled', true)->count();
            $totalReplies = $user->postReplyStates()->count();

            $stats['facebook'] = [
                'accounts_count' => $user->facebookAccounts()->count(),
                'pages_count' => $user->facebookPages()->count(),
                'posts_count' => $user->posts()->count(),
                'active_posts' => $activePosts,
                'inactive_posts' => $user->posts()->where('enabled', false)->count(),
                'total_replies_sent' => $totalReplies,
                'auto_replies_this_month' => $monthlyStats->auto_replies_count ?? 0,
                'active_templates' => $user->autoReplyTemplates()->count(),
                'posts_with_auto_reply' => $user->posts()->where('reply_to_comment_enabled', true)->count(),
                'posts_with_private_message' => $user->posts()->where('reply_to_private_message_enabled', true)->count(),
            ];
        }

        if ($package?->feature_whatsapp) {
            $instances = $user->whatsappAccounts;
            $totalMessages = 0;
            $sentMessages = 0;
            $receivedMessages = 0;
            $todayMessages = 0;
            $unreadMessages = 0;

            foreach ($instances as $instance) {
                $totalMessages += $instance->messages()->count();
                $sentMessages += $instance->messages()->where('from_me', true)->count();
                $receivedMessages += $instance->messages()->where('from_me', false)->count();
                $todayMessages += $instance->getTodayMessagesCount();
                $unreadMessages += $instance->getUnreadMessagesCount();
            }

            $stats['whatsapp'] = [
                'instances_count' => $instances->count(),
                'connected_instances' => $instances->where('status', 'open')->count(),
                'disconnected_instances' => $instances->where('status', '!=', 'open')->count(),
                'total_messages' => $totalMessages,
                'sent_messages' => $sentMessages,
                'received_messages' => $receivedMessages,
                'today_messages' => $todayMessages,
                'unread_messages' => $unreadMessages,
                'auto_replies_this_month' => $monthlyStats->whatsapp_auto_replies_count ?? 0,
                'instances_with_auto_reply' => $user->whatsappAccounts()
                    ->whereHas('autoReply', fn($q) => $q->where('is_active', true))
                    ->count(),
                'instances_with_ai' => $user->whatsappAccounts()
                    ->whereHas('aiReply', fn($q) => $q->where('is_active', true))
                    ->count(),
            ];
        }

        $stats['support'] = [
            'open_tickets' => $user->tickets()->where('status', 'open')->count(),
            'pending_tickets' => $user->tickets()->where('status', 'pending')->count(),
            'closed_tickets' => $user->tickets()->where('status', 'closed')->count(),
            'total_tickets' => $user->tickets()->count(),
        ];

        return $stats;
    }

    private function getFeatures($package): array
    {
        if (!$package) {
            return [];
        }

        return [
            'facebook' => $package->feature_facebook ?? false,
            'facebook_auto_reply' => $package->feature_facebook_auto_reply ?? false,
            'whatsapp' => $package->feature_whatsapp ?? false,
            'whatsapp_auto_reply' => $package->feature_whatsapp_auto_reply ?? false,
            'whatsapp_ai_reply' => $package->feature_whatsapp_ai_reply ?? false,
            'whatsapp_developer' => $package->feature_whatsapp_developer ?? false,
            'whatsapp_openai_support' => $package->feature_whatsapp_openai_support ?? false,
            '24_support' => $package->feature_24_support ?? false,
            'unlimited_replies' => $package->feature_unlimited_replies ?? false,
            'advanced_reports' => $package->feature_advanced_reports ?? false,
            'multiple_accounts' => $package->feature_multiple_accounts ?? false,
            'custom_templates' => $package->feature_custom_templates ?? false,
            'priority_processing' => $package->feature_priority_processing ?? false,
        ];
    }

    private function getLimitsUsage($user, $package): array
    {
        if (!$package) {
            return [];
        }

        $monthlyStats = $user->getCurrentMonthUsage();

        return [
            'facebook_accounts' => [
                'used' => $user->facebookAccounts()->count(),
                'limit' => $package->limit_facebook_accounts,
                'unlimited' => $package->limit_facebook_accounts === null,
                'percentage' => $this->calculatePercentage($user->facebookAccounts()->count(), $package->limit_facebook_accounts),
            ],
            'facebook_pages' => [
                'used' => $user->facebookPages()->count(),
                'limit' => $package->limit_facebook_pages,
                'unlimited' => $package->limit_facebook_pages === null,
                'percentage' => $this->calculatePercentage($user->facebookPages()->count(), $package->limit_facebook_pages),
            ],
            'whatsapp_accounts' => [
                'used' => $user->whatsappAccounts()->count(),
                'limit' => $package->limit_whatsapp_accounts,
                'unlimited' => $package->limit_whatsapp_accounts === null,
                'percentage' => $this->calculatePercentage($user->whatsappAccounts()->count(), $package->limit_whatsapp_accounts),
            ],
            'auto_replies_per_month' => [
                'used' => $monthlyStats->auto_replies_count ?? 0,
                'limit' => $package->limit_auto_replies_per_month,
                'unlimited' => $package->limit_auto_replies_per_month === null,
                'percentage' => $this->calculatePercentage($monthlyStats->auto_replies_count ?? 0, $package->limit_auto_replies_per_month),
            ],
            'whatsapp_auto_replies_per_month' => [
                'used' => $monthlyStats->whatsapp_auto_replies_count ?? 0,
                'limit' => $package->limit_whatsapp_auto_replies_per_month,
                'unlimited' => $package->limit_whatsapp_auto_replies_per_month === null,
                'percentage' => $this->calculatePercentage($monthlyStats->whatsapp_auto_replies_count ?? 0, $package->limit_whatsapp_auto_replies_per_month),
            ],
            'templates' => [
                'used' => $user->autoReplyTemplates()->count(),
                'limit' => $package->limit_templates,
                'unlimited' => $package->limit_templates === null,
                'percentage' => $this->calculatePercentage($user->autoReplyTemplates()->count(), $package->limit_templates),
            ],
        ];
    }

    private function calculatePercentage($used, $limit): ?int
    {
        if ($limit === null || $limit === 0) {
            return null;
        }
        return min(round(($used / $limit) * 100), 100);
    }

    private function getQuickStats($user): array
    {
        return [
            'total_facebook_accounts' => $user->facebookAccounts()->count(),
            'total_facebook_pages' => $user->facebookPages()->count(),
            'total_whatsapp_instances' => $user->whatsappAccounts()->count(),
            'connected_whatsapp' => $user->whatsappAccounts()->where('status', 'open')->count(),
            'total_templates' => $user->autoReplyTemplates()->count(),
            'total_posts' => $user->posts()->count(),
            'active_posts' => $user->posts()->where('enabled', true)->count(),
        ];
    }

    private function getTimeBasedStats($user): array
    {
        $now = Carbon::now();

        return [
            'today' => [
                'date' => $now->toDateString(),
                'facebook_replies' => $user->postReplyStates()->whereDate('created_at', $now)->count(),
                'whatsapp_messages' => $this->getWhatsappMessagesCount($user, $now, $now),
            ],
            'this_week' => [
                'start' => $now->copy()->startOfWeek()->toDateString(),
                'end' => $now->copy()->endOfWeek()->toDateString(),
                'facebook_replies' => $user->postReplyStates()
                    ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                    ->count(),
                'whatsapp_messages' => $this->getWhatsappMessagesCount($user, $now->copy()->startOfWeek(), $now->copy()->endOfWeek()),
            ],
            'this_month' => [
                'month' => $now->format('Y-m'),
                'facebook_replies' => $user->postReplyStates()
                    ->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count(),
                'whatsapp_messages' => $this->getWhatsappMessagesCount($user, $now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            ],
        ];
    }

    private function getWhatsappMessagesCount($user, $startDate, $endDate): int
    {
        $count = 0;
        foreach ($user->whatsappAccounts as $instance) {
            $count += $instance->messages()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
        }
        return $count;
    }

    private function getRecentActivity($user): array
    {
        $activities = [];

        $recentPosts = $user->posts()
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($p) => [
                'type' => 'post_created',
                'title' => 'تم إنشاء منشور جديد',
                'post_id' => $p->post_id,
                'enabled' => $p->enabled,
                'created_at' => $p->created_at,
            ]);

        $recentTickets = $user->tickets()
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($t) => [
                'type' => 'ticket',
                'title' => 'تذكرة دعم: ' . ($t->subject ?? 'بدون عنوان'),
                'status' => $t->status,
                'created_at' => $t->created_at,
            ]);

        $recentInstances = $user->whatsappAccounts()
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($i) => [
                'type' => 'whatsapp_instance',
                'title' => 'حساب واتساب: ' . $i->instance_name,
                'status' => $i->status,
                'phone' => $i->phone_number,
                'created_at' => $i->created_at,
            ]);

        return [
            'posts' => $recentPosts,
            'tickets' => $recentTickets,
            'whatsapp_instances' => $recentInstances,
        ];
    }
}
