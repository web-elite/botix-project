<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            'sub_1_1' => [
                'name' => '1 ماهه - 1 کاربره',
                'amount' => 199000,
                'duration' => 1,
                'duration_text' => '1 ماه',
                'users_count' => 1,
                'description' => 'پلن یک‌ماهه برای 1 کاربر',
                'is_active'     => true,
            ],
            'sub_1_2' => [
                'name' => '1 ماهه - 2 کاربره',
                'amount' => 349000,
                'duration' => 1,
                'duration_text' => '1 ماه',
                'users_count' => 2,
                'description' => 'پلن یک‌ماهه برای 2 کاربر',
                'is_active'     => true,
            ],
            'sub_2_1' => [
                'name' => '2 ماهه - 1 کاربره',
                'amount' => 379000,
                'duration' => 2,
                'duration_text' => '2 ماه',
                'users_count' => 1,
                'description' => 'پلن دو‌ماهه برای 1 کاربر',
                'is_active'     => true,
            ],
            'sub_2_2' => [
                'name' => '2 ماهه - 2 کاربره',
                'amount' => 659000,
                'duration' => 2,
                'duration_text' => '2 ماه',
                'users_count' => 2,
                'description' => 'پلن دو‌ماهه برای 2 کاربر',
                'is_active'     => true,
            ],
            'sub_3_1' => [
                'name' => '3 ماهه - 1 کاربره',
                'amount' => 539000,
                'duration' => 3,
                'duration_text' => '3 ماه',
                'users_count' => 1,
                'description' => 'پلن سه‌ماهه برای 1 کاربر',
                'is_active'     => true,
            ],
            'sub_3_2' => [
                'name' => '3 ماهه - 2 کاربره',
                'amount' => 949000,
                'duration' => 3,
                'duration_text' => '3 ماه',
                'users_count' => 2,
                'description' => 'پلن سه‌ماهه برای 2 کاربر',
                'is_active'     => true,
            ],
            'vol_10' => [
                'name' => '10 گیگابایت - 1 ماهه',
                'amount' => 35000, // 2500 × 10
                'duration' => 1,
                'gigabytes' => 10,
                'duration_text' => '1 ماه',
                'description' => 'پلن 10 گیگابایتی یک‌ماهه (گیگی ۲۵۰۰ تومان)',
                'is_active'     => false,
                'users_count' => 999,
            ],
            'vol_20' => [
                'name' => '20 گیگابایت - 1 ماهه',
                'amount' => 47000, // گیگی 2350
                'duration' => 1,
                'gigabytes' => 20,
                'duration_text' => '1 ماه',
                'description' => 'پلن 20 گیگابایتی یک‌ماهه (گیگی ۲۳۵۰ تومان)',
                'is_active'     => true,
                'users_count' => 999,
            ],
            'vol_40' => [
                'name' => '40 گیگابایت - 1 ماهه',
                'amount' => 88000, // گیگی 2200
                'duration' => 1,
                'gigabytes' => 40,
                'duration_text' => '1 ماه',
                'description' => 'پلن 40 گیگابایتی یک‌ماهه (گیگی ۲۲۰۰ تومان)',
                'is_active'     => true,
                'users_count' => 999,
            ],
            'vol_60' => [
                'name' => '60 گیگابایت - 1 ماهه',
                'amount' => 126000, // گیگی 2100
                'duration' => 1,
                'gigabytes' => 60,
                'duration_text' => '1 ماه',
                'description' => 'پلن 60 گیگابایتی یک‌ماهه (گیگی ۲۱۰۰ تومان)',
                'is_active'     => true,
                'users_count' => 999,
            ],
            'vol_90' => [
                'name' => '90 گیگابایت - 1 ماهه',
                'amount' => 180000, // گیگی 2000
                'duration' => 1,
                'gigabytes' => 90,
                'duration_text' => '1 ماه',
                'description' => 'پلن 90 گیگابایتی یک‌ماهه (گیگی ۲۰۰۰ تومان)',
                'is_active'     => true,
                'users_count' => 999,
            ],
        ];

        foreach ($plans as $slug => $plan) {
            SubscriptionPlan::updateOrInsert(
                ['slug' => $slug],
                [
                    'name'          => $plan['name'],
                    'amount'        => $plan['amount'],
                    'duration'      => $plan['duration'],
                    'duration_text' => $plan['duration_text'],
                    'users_count'   => $plan['users_count'],
                    'description'   => $plan['description'],
                    'is_active'     => $plan['is_active'],
                    'gigabytes'     => $plan['gigabytes'] ?? 0, // Default to 0 if not set
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }
}
