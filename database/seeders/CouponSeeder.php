<?php

namespace Database\Seeders;

use App\Models\Coupons;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'coupon_code'    => 'WELCOME10',
                'discount_type'  => 'percentage',
                'discount_value' => 10,
                'expiry_date'    => Carbon::now()->addMonths(3),
                'usage_limit'    => 200,
                'used_count'     => 12,
                'is_active'      => true,
            ],
            [
                'coupon_code'    => 'SPRING25',
                'discount_type'  => 'percentage',
                'discount_value' => 25,
                'expiry_date'    => Carbon::now()->addMonth(),
                'usage_limit'    => 100,
                'used_count'     => 5,
                'is_active'      => true,
            ],
            [
                'coupon_code'    => 'FIX5000',
                'discount_type'  => 'fixed',
                'discount_value' => 5000,
                'expiry_date'    => Carbon::now()->addMonths(2),
                'usage_limit'    => 50,
                'used_count'     => 0,
                'is_active'      => true,
            ],
            [
                'coupon_code'    => 'VIP50',
                'discount_type'  => 'percentage',
                'discount_value' => 50,
                'expiry_date'    => Carbon::now()->addWeeks(2),
                'usage_limit'    => 20,
                'used_count'     => 18,
                'is_active'      => true,
            ],
            [
                'coupon_code'    => 'SUMMER15',
                'discount_type'  => 'percentage',
                'discount_value' => 15,
                'expiry_date'    => Carbon::now()->addMonths(4),
                'usage_limit'    => null,
                'used_count'     => 3,
                'is_active'      => true,
            ],
            [
                'coupon_code'    => 'BLACKFRIDAY',
                'discount_type'  => 'percentage',
                'discount_value' => 40,
                'expiry_date'    => Carbon::now()->subDays(10),
                'usage_limit'    => 500,
                'used_count'     => 487,
                'is_active'      => false,
            ],
            [
                'coupon_code'    => 'TEST1000',
                'discount_type'  => 'fixed',
                'discount_value' => 1000,
                'expiry_date'    => Carbon::now()->addDays(7),
                'usage_limit'    => 10,
                'used_count'     => 2,
                'is_active'      => false,
            ],
            [
                'coupon_code'    => 'HOLIDAY20',
                'discount_type'  => 'percentage',
                'discount_value' => 20,
                'expiry_date'    => Carbon::now()->addMonths(6),
                'usage_limit'    => 300,
                'used_count'     => 0,
                'is_active'      => true,
            ],
        ];

        foreach ($rows as $row) {
            Coupons::firstOrCreate(
                ['coupon_code' => $row['coupon_code']],
                $row
            );
        }
    }
}
