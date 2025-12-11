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

                // WhatsApp
                'feature_whatsapp' => false,
                'feature_whatsapp_auto_reply' => false,
                'feature_whatsapp_ai_reply' => false,
                'feature_whatsapp_openai_support' => false,
                'feature_whatsapp_developer' => false,
                'limit_whatsapp_accounts' => 0,
                'limit_whatsapp_auto_replies_per_month' => 0,

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

                // WhatsApp
                'feature_whatsapp' => true,
                'feature_whatsapp_auto_reply' => true,
                'feature_whatsapp_ai_reply' => false,
                'feature_whatsapp_openai_support' => false,
                'feature_whatsapp_developer' => false,
                'limit_whatsapp_accounts' => 1,
                'limit_whatsapp_auto_replies_per_month' => 1000,

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

                // WhatsApp
                'feature_whatsapp' => true,
                'feature_whatsapp_auto_reply' => true,
                'feature_whatsapp_ai_reply' => true,
                'feature_whatsapp_openai_support' => true,
                'feature_whatsapp_developer' => false,
                'limit_whatsapp_accounts' => 5,
                'limit_whatsapp_auto_replies_per_month' => null,

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

                // WhatsApp
                'feature_whatsapp' => true,
                'feature_whatsapp_auto_reply' => true,
                'feature_whatsapp_ai_reply' => true,
                'feature_whatsapp_openai_support' => true,
                'feature_whatsapp_developer' => true,
                'limit_whatsapp_accounts' => null,
                'limit_whatsapp_auto_replies_per_month' => null,

                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['name' => $package['name']], // Use name as unique identifier to avoid duplicates
                $package
            );
        }
    }
}
