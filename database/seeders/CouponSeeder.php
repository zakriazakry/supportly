<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            // كوبونات تعبئة المحفظة
            [
                'code' => 'WALLET5',
                'type' => 'wallet_recharge',
                'value' => 5.00,
                'value_type' => 'fixed',
                'max_uses' => 100,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'description' => 'كوبون تعبئة محفظة بقيمة 5 دولار',
            ],
            [
                'code' => 'WALLET10',
                'type' => 'wallet_recharge',
                'value' => 10.00,
                'value_type' => 'fixed',
                'max_uses' => 50,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'description' => 'كوبون تعبئة محفظة بقيمة 10 دولار',
            ],
            [
                'code' => 'WALLET20',
                'type' => 'wallet_recharge',
                'value' => 20.00,
                'value_type' => 'fixed',
                'max_uses' => 25,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'description' => 'كوبون تعبئة محفظة بقيمة 20 دولار',
            ],
            [
                'code' => 'WALLET50',
                'type' => 'wallet_recharge',
                'value' => 50.00,
                'value_type' => 'fixed',
                'max_uses' => 10,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'description' => 'كوبون تعبئة محفظة بقيمة 50 دولار',
            ],

            // كوبونات خصم على الاشتراكات
            [
                'code' => 'DISCOUNT10',
                'type' => 'subscription_discount',
                'value' => 10.00,
                'value_type' => 'percentage',
                'max_uses' => 200,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'description' => 'خصم 10% على أي اشتراك',
            ],
            [
                'code' => 'DISCOUNT20',
                'type' => 'subscription_discount',
                'value' => 20.00,
                'value_type' => 'percentage',
                'max_uses' => 100,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'description' => 'خصم 20% على أي اشتراك',
            ],
            [
                'code' => 'DISCOUNT50',
                'type' => 'subscription_discount',
                'value' => 50.00,
                'value_type' => 'percentage',
                'max_uses' => 20,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'description' => 'خصم 50% على أي اشتراك',
            ],
            [
                'code' => 'FIXED5OFF',
                'type' => 'subscription_discount',
                'value' => 5.00,
                'value_type' => 'fixed',
                'max_uses' => 150,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'description' => 'خصم 5 دولار على أي اشتراك',
            ],
            [
                'code' => 'FIXED10OFF',
                'type' => 'subscription_discount',
                'value' => 10.00,
                'value_type' => 'fixed',
                'max_uses' => 75,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(6),
                'is_active' => true,
                'description' => 'خصم 10 دولار على أي اشتراك',
            ],

            // كوبون ترحيبي
            [
                'code' => 'WELCOME2025',
                'type' => 'wallet_recharge',
                'value' => 15.00,
                'value_type' => 'fixed',
                'max_uses' => null, // غير محدود
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addYear(),
                'is_active' => true,
                'description' => 'كوبون ترحيبي - تعبئة محفظة بقيمة 15 دولار',
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
