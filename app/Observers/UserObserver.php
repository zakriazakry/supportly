<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // إنشاء محفظة افتراضية للمستخدم الجديد
        Wallet::create([
            'user_id' => $user->id,
            'currency' => 'LYD', // يمكن تغييرها حسب الإعدادات
            'balance' => 0.00,
            'status' => 1, // نشطة
            'is_default' => true,
        ]);
    }
}
