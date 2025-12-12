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
                'max_uses' => 1,
                'used_count' => 0,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'is_active' => true,
                'description' => 'كوبون تعبئة محفظة بقيمة 5 دولار',
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
