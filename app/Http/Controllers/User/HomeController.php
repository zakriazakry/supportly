<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MonthlyUsageStat;
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
            ],
            'subscription' => $this->getSubscriptionData($subscription, $package),
            'wallet' => $this->getWalletData($wallet),
            'statistics' => $this->getStatistics($user, $package, $monthlyStats),
            'features' => $this->getFeatures($package),
            'limits' => $this->getLimitsUsage($user, $package),
            'quick_stats' => $this->getQuickStats($user),
        ];

        return responseFormat($data);
    }

    private function getSubscriptionData($subscription, $package): array
    {
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'message' => 'لا يوجد اشتراك نشط',
            ];
        }

        return [
            'has_subscription' => true,
            'package_name' => $package->name,
            'package_price' => $package->price,
            'currency' => $package->currency,
            'status' => $subscription->status,
            'start_date' => $subscription->start_date,
            'end_date' => $subscription->end_date,
            'days_remaining' => Carbon::parse($subscription->end_date)->diffInDays(now()),
            'auto_renew' => $subscription->auto_renew,
        ];
    }

    private function getWalletData($wallet): array
    {
        if (!$wallet) {
            return [
                'has_wallet' => false,
                'balance' => 0,
            ];
        }

        return [
            'has_wallet' => true,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
        ];
    }

    private function getStatistics($user, $package, $monthlyStats): array
    {
        $stats = [];

        if ($package?->feature_facebook) {
            $stats['facebook'] = [
                'accounts_count' => $user->facebookAccounts()->count(),
                'pages_count' => $user->facebookPages()->count(),
                'posts_count' => $user->posts()->count(),
                'auto_replies_sent' => $monthlyStats->auto_replies_count ?? 0,
                'active_templates' => $user->autoReplyTemplates()->count(),
            ];
        }

        if ($package?->feature_whatsapp) {
            $stats['whatsapp'] = [
                'instances_count' => $user->whatsappAccounts()->count(),
                'connected_instances' => $user->whatsappAccounts()->where('status', 'open')->count(),
                'messages_sent' => $user->whatsappAccounts()->withCount('messages')->get()->sum('messages_count'),
                'auto_replies_sent' => $monthlyStats->whatsapp_auto_replies_count ?? 0,
            ];
        }

        $stats['support'] = [
            'open_tickets' => $user->tickets()->where('status', 'open')->count(),
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

        return [
            'facebook_accounts' => [
                'used' => $user->facebookAccounts()->count(),
                'limit' => $package->limit_facebook_accounts,
                'unlimited' => $package->limit_facebook_accounts === null,
            ],
            'facebook_pages' => [
                'used' => $user->facebookPages()->count(),
                'limit' => $package->limit_facebook_pages,
                'unlimited' => $package->limit_facebook_pages === null,
            ],
            'whatsapp_accounts' => [
                'used' => $user->whatsappAccounts()->count(),
                'limit' => $package->limit_whatsapp_accounts,
                'unlimited' => $package->limit_whatsapp_accounts === null,
            ],
            'auto_replies_per_month' => [
                'used' => $user->getCurrentMonthUsage()->auto_replies_count ?? 0,
                'limit' => $package->limit_auto_replies_per_month,
                'unlimited' => $package->limit_auto_replies_per_month === null,
            ],
            'templates' => [
                'used' => $user->autoReplyTemplates()->count(),
                'limit' => $package->limit_templates,
                'unlimited' => $package->limit_templates === null,
            ],
        ];
    }

    private function getQuickStats($user): array
    {
        return [
            'total_facebook_accounts' => $user->facebookAccounts()->count(),
            'total_facebook_pages' => $user->facebookPages()->count(),
            'total_whatsapp_instances' => $user->whatsappAccounts()->count(),
            'total_templates' => $user->autoReplyTemplates()->count(),
            'total_posts' => $user->posts()->count(),
        ];
    }
}
