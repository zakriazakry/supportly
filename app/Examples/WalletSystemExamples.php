<?php

/**
 * أمثلة على استخدام نظام المحفظة والكوبونات
 * Wallet & Coupons System Usage Examples
 */

namespace App\Examples;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Coupon;
use App\Models\Package;

class WalletSystemExamples
{
    /**
     * مثال 1: الحصول على المحفظة النشطة
     */
    public function example1_getActiveWallet()
    {
        $user = User::find(1);
        $wallet = $user->getActiveWallet();

        echo "رصيد المحفظة: " . $wallet->balance;
        echo "العملة: " . $wallet->currency;
    }

    /**
     * مثال 2: إضافة رصيد للمحفظة
     */
    public function example2_addCredit()
    {
        $user = User::find(1);

        $transaction = $user->addWalletCredit(
            amount: 50.00,
            description: 'تعبئة رصيد يدوية',
            referenceType: 'manual',
            referenceId: null,
            metadata: ['admin_id' => 1, 'note' => 'هدية']
        );

        echo "تمت إضافة: " . $transaction->amount;
        echo "الرصيد الجديد: " . $transaction->balance_after;
    }

    /**
     * مثال 3: خصم رصيد من المحفظة
     */
    public function example3_deductBalance()
    {
        $user = User::find(1);

        try {
            $transaction = $user->deductWalletBalance(
                amount: 10.00,
                description: 'شراء خدمة إضافية',
                referenceType: 'service',
                referenceId: 5
            );

            echo "تم الخصم: " . $transaction->amount;
            echo "الرصيد المتبقي: " . $transaction->balance_after;
        } catch (\Exception $e) {
            echo "خطأ: " . $e->getMessage(); // رصيد غير كافٍ
        }
    }

    /**
     * مثال 4: تطبيق كوبون تعبئة محفظة
     */
    public function example4_applyWalletCoupon()
    {
        $user = User::find(1);

        try {
            $result = $user->applyCoupon('WALLET10');

            if ($result['type'] === 'wallet_recharge') {
                echo "تم تعبئة المحفظة بمبلغ: " . $result['amount'];
                echo "الرسالة: " . $result['message'];
            }
        } catch (\Exception $e) {
            echo "خطأ: " . $e->getMessage();
        }
    }

    /**
     * مثال 5: شراء اشتراك من المحفظة بدون كوبون
     */
    public function example5_purchaseWithoutCoupon()
    {
        $user = User::find(1);
        $packageId = 1; // Basic Package

        try {
            $subscription = $user->purchaseSubscriptionWithWallet($packageId);

            echo "تم شراء الاشتراك: " . $subscription->package->name;
            echo "المبلغ المدفوع: " . $subscription->paid_amount;
            echo "تاريخ الانتهاء: " . $subscription->end_date;
        } catch (\Exception $e) {
            echo "خطأ: " . $e->getMessage();
        }
    }

    /**
     * مثال 6: شراء اشتراك من المحفظة مع كوبون خصم
     */
    public function example6_purchaseWithCoupon()
    {
        $user = User::find(1);
        $packageId = 2; // Pro Package
        $couponCode = 'DISCOUNT20'; // خصم 20%

        try {
            $subscription = $user->purchaseSubscriptionWithWallet($packageId, $couponCode);

            echo "تم شراء الاشتراك: " . $subscription->package->name;
            echo "السعر الأصلي: " . $subscription->package->price;
            echo "المبلغ المدفوع بعد الخصم: " . $subscription->paid_amount;
        } catch (\Exception $e) {
            echo "خطأ: " . $e->getMessage();
        }
    }

    /**
     * مثال 7: التبديل بين المحافظ
     */
    public function example7_switchWallet()
    {
        $user = User::find(1);
        $wallets = $user->wallets;

        if ($wallets->count() > 1) {
            $newWallet = $wallets->where('status', 0)->first();

            try {
                $newWallet->activate();
                echo "تم تفعيل المحفظة رقم: " . $newWallet->id;
            } catch (\Exception $e) {
                echo "خطأ: " . $e->getMessage();
            }
        }
    }

    /**
     * مثال 8: عرض معاملات المحفظة
     */
    public function example8_viewTransactions()
    {
        $user = User::find(1);
        $wallet = $user->getActiveWallet();

        $transactions = $wallet->transactions()
            ->latest()
            ->take(10)
            ->get();

        foreach ($transactions as $transaction) {
            echo "النوع: " . ($transaction->type === 'credit' ? 'إضافة' : 'خصم');
            echo "المبلغ: " . $transaction->amount;
            echo "الوصف: " . $transaction->description;
            echo "التاريخ: " . $transaction->created_at;
            echo "---";
        }
    }

    /**
     * مثال 9: التحقق من صلاحية كوبون
     */
    public function example9_checkCouponValidity()
    {
        $coupon = Coupon::findByCode('WALLET10');

        if ($coupon) {
            echo "الكوبون موجود";
            echo "نوع الكوبون: " . $coupon->type;
            echo "القيمة: " . $coupon->value;

            if ($coupon->isValid()) {
                echo "الكوبون صالح للاستخدام";
                $remaining = $coupon->getRemainingUses();
                echo "الاستخدامات المتبقية: " . ($remaining ?? 'غير محدود');
            } else {
                echo "الكوبون غير صالح";
            }
        } else {
            echo "الكوبون غير موجود";
        }
    }

    /**
     * مثال 10: إنشاء كوبون جديد
     */
    public function example10_createCoupon()
    {
        $coupon = Coupon::create([
            'code' => 'SPECIAL100',
            'type' => 'wallet_recharge',
            'value' => 100.00,
            'value_type' => 'fixed',
            'max_uses' => 5,
            'used_count' => 0,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(1),
            'is_active' => true,
            'description' => 'كوبون خاص - تعبئة 100 دولار',
        ]);

        echo "تم إنشاء الكوبون: " . $coupon->code;
    }

    /**
     * مثال 11: إنشاء محفظة إضافية
     */
    public function example11_createAdditionalWallet()
    {
        $user = User::find(1);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 0.00,
            'status' => 0, // غير نشطة
            'is_default' => false,
        ]);

        echo "تم إنشاء محفظة جديدة بعملة: " . $wallet->currency;
    }

    /**
     * مثال 12: التحقق من رصيد المحفظة قبل الشراء
     */
    public function example12_checkBalanceBeforePurchase()
    {
        $user = User::find(1);
        $wallet = $user->getActiveWallet();
        $package = Package::find(1);

        if ($wallet->hasSufficientBalance($package->price)) {
            echo "الرصيد كافٍ للشراء";
            echo "رصيد المحفظة: " . $wallet->balance;
            echo "سعر الباقة: " . $package->price;
        } else {
            echo "الرصيد غير كافٍ";
            $needed = $package->price - $wallet->balance;
            echo "تحتاج إلى: " . $needed . " إضافية";
        }
    }

    /**
     * مثال 13: عرض جميع محافظ المستخدم
     */
    public function example13_listAllWallets()
    {
        $user = User::find(1);
        $wallets = $user->wallets;

        foreach ($wallets as $wallet) {
            echo "المحفظة رقم: " . $wallet->id;
            echo "العملة: " . $wallet->currency;
            echo "الرصيد: " . $wallet->balance;
            echo "الحالة: " . ($wallet->isActive() ? 'نشطة' : 'معطلة');
            echo "عدد المعاملات: " . $wallet->transactions()->count();
            echo "---";
        }
    }

    /**
     * مثال 14: استخدام Scopes
     */
    public function example14_useScopes()
    {
        // الحصول على الكوبونات النشطة فقط
        $activeCoupons = Coupon::active()->get();

        // الحصول على كوبونات تعبئة المحفظة
        $walletCoupons = Coupon::walletRecharge()->active()->get();

        // الحصول على كوبونات خصم الاشتراكات
        $discountCoupons = Coupon::subscriptionDiscount()->active()->get();

        // الحصول على المحافظ النشطة
        $activeWallets = Wallet::active()->get();

        // الحصول على معاملات الإضافة فقط
        $creditTransactions = WalletTransaction::credit()->get();
    }

    /**
     * مثال 15: سيناريو كامل - من التسجيل إلى الشراء
     */
    public function example15_completeScenario()
    {
        // 1. إنشاء مستخدم جديد (سيتم إنشاء محفظة افتراضية تلقائياً)
        $user = User::create([
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('password'),
            'phone' => '0123456789',
        ]);

        echo "تم إنشاء المستخدم والمحفظة الافتراضية";

        // 2. تطبيق كوبون ترحيبي
        $result = $user->applyCoupon('WELCOME2025');
        echo "تم تعبئة المحفظة بمبلغ: " . $result['amount'];

        // 3. شراء اشتراك مع كوبون خصم
        $subscription = $user->purchaseSubscriptionWithWallet(1, 'DISCOUNT10');
        echo "تم شراء الاشتراك بنجاح";

        // 4. عرض الرصيد المتبقي
        $wallet = $user->getActiveWallet();
        echo "الرصيد المتبقي: " . $wallet->balance;
    }
}
