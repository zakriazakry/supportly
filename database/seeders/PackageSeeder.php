<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'الباقة المجانية',
                'description' => 'باقة تجريبية للبدء',
                'price' => 0,
                'currency' => 'LYD',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'feature_24_support' => false,
                'feature_unlimited_replies' => false,
                'feature_advanced_reports' => false,
                'feature_multiple_accounts' => false,
                'feature_custom_templates' => false,
                'feature_priority_processing' => false,
                'limit_facebook_accounts' => 1,
                'limit_facebook_pages' => 2,
                'limit_auto_replies_per_month' => 100,
                'limit_templates' => 3,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'الباقة الأساسية',
                'description' => 'مناسبة للأفراد والمشاريع الصغيرة',
                'price' => 99,
                'currency' => 'LYD',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'feature_24_support' => false,
                'feature_unlimited_replies' => false,
                'feature_advanced_reports' => false,
                'feature_multiple_accounts' => true,
                'feature_custom_templates' => true,
                'feature_priority_processing' => false,
                'limit_facebook_accounts' => 2,
                'limit_facebook_pages' => 5,
                'limit_auto_replies_per_month' => 500,
                'limit_templates' => 10,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'الباقة الاحترافية',
                'description' => 'الأنسب للشركات والأعمال المتوسطة',
                'price' => 299,
                'currency' => 'LYD',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'feature_24_support' => true,
                'feature_unlimited_replies' => true,
                'feature_advanced_reports' => true,
                'feature_multiple_accounts' => true,
                'feature_custom_templates' => true,
                'feature_priority_processing' => true,
                'limit_facebook_accounts' => null, // غير محدود
                'limit_facebook_pages' => null, // غير محدود
                'limit_auto_replies_per_month' => null, // غير محدود
                'limit_templates' => null, // غير محدود
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'باقة الأعمال',
                'description' => 'للشركات الكبيرة والمؤسسات',
                'price' => 599,
                'currency' => 'LYD',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'feature_24_support' => true,
                'feature_unlimited_replies' => true,
                'feature_advanced_reports' => true,
                'feature_multiple_accounts' => true,
                'feature_custom_templates' => true,
                'feature_priority_processing' => true,
                'limit_facebook_accounts' => null,
                'limit_facebook_pages' => null,
                'limit_auto_replies_per_month' => null,
                'limit_templates' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
