<?php

use Illuminate\Database\Seeder;

class PromoCampaignsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('promo_campaigns')->delete();
        
        \DB::table('promo_campaigns')->insert(array (
            0 => 
            array (
                'id_promo_campaign' => 1,
                'created_by' => NULL,
                'last_updated_by' => NULL,
                'campaign_name' => 'Referral',
                'promo_title' => 'Referral',
                'code_type' => 'Multiple',
                'prefix_code' => NULL,
                'number_last_code' => NULL,
                'total_coupon' => 0,
                'date_start' => '2020-01-01 00:00:00',
                'date_end' => '2020-12-31 23:59:59',
                'is_all_outlet' => '1',
                'promo_type' => 'Referral',
                'user_type' => 'New user',
                'specific_user' => NULL,
                'step_complete' => 1,
                'created_at' => NULL,
                'updated_at' => NULL,
                'used_code' => 0,
                'limitation_usage' => 0,
            ),
        ));
        
        
    }
}