<?php
namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            'sub_1_1' => ['name' => '1 ماهه - 1 کاربره', 'amount' => 199000, 'duration' => 1, 'duration_text' => '1 ماه', 'users_count' => 1, 'description' => 'پلن یک‌ماهه برای 1 کاربر'],
            'sub_1_2' => ['name' => '1 ماهه - 2 کاربره', 'amount' => 349000, 'duration' => 1, 'duration_text' => '1 ماه', 'users_count' => 2, 'description' => 'پلن یک‌ماهه برای 2 کاربر'],
            'sub_2_1' => ['name' => '2 ماهه - 1 کاربره', 'amount' => 399000, 'duration' => 2, 'duration_text' => '2 ماه', 'users_count' => 1, 'description' => 'پلن دو‌ماهه برای 1 کاربر'],
            'sub_2_2' => ['name' => '2 ماهه - 2 کاربره', 'amount' => 699000, 'duration' => 2, 'duration_text' => '2 ماه', 'users_count' => 2, 'description' => 'پلن دو‌ماهه برای 2 کاربر'],
            'sub_3_1' => ['name' => '3 ماهه - 1 کاربره', 'amount' => 579000, 'duration' => 3, 'duration_text' => '3 ماه', 'users_count' => 1, 'description' => 'پلن سه‌ماهه برای 1 کاربر'],
            'sub_3_2' => ['name' => '3 ماهه - 2 کاربره', 'amount' => 999000, 'duration' => 3, 'duration_text' => '3 ماه', 'users_count' => 2, 'description' => 'پلن سه‌ماهه برای 2 کاربر'],
        ];

        foreach ($plans as $slug => $plan) {
            SubscriptionPlan::create([
                'slug'          => $slug,
                'name'          => $plan['name'],
                'amount'        => $plan['amount'],
                'duration'      => $plan['duration'],
                'duration_text' => $plan['duration_text'],
                'users_count'   => $plan['users_count'],
                'description'   => $plan['description'],
                'is_active'     => true,
            ]);
        }
    }
}
