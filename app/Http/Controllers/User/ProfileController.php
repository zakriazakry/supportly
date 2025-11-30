<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Get user profile information.
     */
    public function index(Request $request)
    {
        $user = $request->user()->with('activeSubscription.package');
        unset($user->activeSubscription);
        return responseFormat([
            'user' => $user,
            'package' => $this->getCurrentSubscription($request),
        ]);
    }

    /**
     * Get current subscription details.
     */
    public function getCurrentSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->getCurrentSubscription();

        if (!$subscription) {
            return responseFormat('لا يوجد اشتراك نشط', 404);
        }

        $subscription->load('package');

        $data = [
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date->format('Y-m-d'),
                'end_date' => $subscription->end_date->format('Y-m-d'),
                'remaining_days' => $subscription->getRemainingDays(),
                'auto_renew' => $subscription->auto_renew,
            ],
            'package' => [
                'id' => $subscription->package->id,
                'name' => $subscription->package->name,
                'description' => $subscription->package->description,
                'price' => $subscription->package->price,
                'currency' => $subscription->package->currency,
                'features' => [
                    '24_support' => $subscription->package->feature_24_support,
                    'unlimited_replies' => $subscription->package->feature_unlimited_replies,
                    'advanced_reports' => $subscription->package->feature_advanced_reports,
                    'multiple_accounts' => $subscription->package->feature_multiple_accounts,
                    'custom_templates' => $subscription->package->feature_custom_templates,
                    'priority_processing' => $subscription->package->feature_priority_processing,
                ],
                'limits' => [
                    'facebook_accounts' => $subscription->package->limit_facebook_accounts,
                    'facebook_pages' => $subscription->package->limit_facebook_pages,
                    'auto_replies_per_month' => $subscription->package->limit_auto_replies_per_month,
                    'templates' => $subscription->package->limit_templates,
                ],
            ],
        ];

        return responseFormat($data);
    }

    /**
     * Get all available packages.
     */
    public function getPackages(Request $request)
    {
        $packages = Package::active()
            ->ordered()
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'currency' => $package->currency,
                    'duration_type' => $package->duration_type,
                    'duration_value' => $package->duration_value,
                    'features' => [
                        '24_support' => $package->feature_24_support,
                        'unlimited_replies' => $package->feature_unlimited_replies,
                        'advanced_reports' => $package->feature_advanced_reports,
                        'multiple_accounts' => $package->feature_multiple_accounts,
                        'custom_templates' => $package->feature_custom_templates,
                        'priority_processing' => $package->feature_priority_processing,
                    ],
                    'limits' => [
                        'facebook_accounts' => $package->limit_facebook_accounts,
                        'facebook_pages' => $package->limit_facebook_pages,
                        'auto_replies_per_month' => $package->limit_auto_replies_per_month,
                        'templates' => $package->limit_templates,
                    ],
                ];
            });

        return responseFormat($packages);
    }

    /**
     * Subscribe to a package.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'auto_renew' => 'boolean',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $package = Package::findOrFail($request->package_id);

        if (!$package->is_active) {
            return responseFormat('هذه الباقة غير متاحة حالياً', 400);
        }

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return responseFormat('لديك اشتراك نشط بالفعل. يرجى إلغاء الاشتراك الحالي أولاً', 400);
        }

        // Calculate dates
        $startDate = Carbon::now();
        if ($package->duration_type === 'monthly') {
            $endDate = $startDate->copy()->addMonths($package->duration_value);
        } else {
            $endDate = $startDate->copy()->addYears($package->duration_value);
        }

        // Create subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active', // في التطبيق الحقيقي، يجب أن يكون 'pending' حتى يتم تأكيد الدفع
            'paid_amount' => $package->price,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'auto_renew' => $request->auto_renew ?? false,
        ]);

        $subscription->load('package');

        return responseFormat($subscription, 'تم الاشتراك بنجاح');
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return responseFormat($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $subscription = $user->getCurrentSubscription();

        if (!$subscription) {
            return responseFormat('لا يوجد اشتراك نشط للإلغاء', 404);
        }

        $subscription->cancel($request->reason);

        return responseFormat('تم إلغاء الاشتراك بنجاح');
    }

    /**
     * Get subscription history.
     */
    public function getSubscriptionHistory(Request $request)
    {
        $user = $request->user();
        $subscriptions = $user->subscriptions()
            ->with('package')
            ->latest()
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'package_name' => $subscription->package->name,
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date->format('Y-m-d'),
                    'status' => $subscription->status,
                    'paid_amount' => $subscription->paid_amount,
                    'payment_method' => $subscription->payment_method,
                    'cancelled_at' => $subscription->cancelled_at?->format('Y-m-d H:i:s'),
                ];
            });

        return responseFormat($subscriptions);
    }

    /**
     * Check user limits and features.
     */
    public function checkLimits(Request $request)
    {
        $user = $request->user();

        if (!$user->hasActiveSubscription()) {
            return responseFormat('لا يوجد اشتراك نشط', 404);
        }

        $data = [
            'limits' => [
                'facebook_accounts' => [
                    'limit' => $user->getLimit('facebook_accounts'),
                    'current' => $user->facebookAccounts()->count(),
                    'can_add' => $user->canAdd('facebook_accounts'),
                ],
                'facebook_pages' => [
                    'limit' => $user->getLimit('facebook_pages'),
                    'current' => $user->facebookPages()->count(),
                    'can_add' => $user->canAdd('facebook_pages'),
                ],
                'templates' => [
                    'limit' => $user->getLimit('templates'),
                    'current' => $user->autoReplyTemplates()->count(),
                    'can_add' => $user->canAdd('templates'),
                ],
            ],
            'features' => [
                '24_support' => $user->hasFeature('24_support'),
                'unlimited_replies' => $user->hasFeature('unlimited_replies'),
                'advanced_reports' => $user->hasFeature('advanced_reports'),
                'multiple_accounts' => $user->hasFeature('multiple_accounts'),
                'custom_templates' => $user->hasFeature('custom_templates'),
                'priority_processing' => $user->hasFeature('priority_processing'),
            ],
        ];

        return responseFormat($data);
    }
}
