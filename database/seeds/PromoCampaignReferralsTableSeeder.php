<?php

use Illuminate\Database\Seeder;

class PromoCampaignReferralsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('promo_campaign_referrals')->delete();
        
        \DB::table('promo_campaign_referrals')->insert(array (
            0 => 
            array (
                'id_promo_campaign_referrals' => 1,
                'referred_promo_type' => 'Cashback',
                'referred_promo_unit' => 'Percent',
                'referred_promo_value' => 10,
                'referred_promo_value_max' => 10000,
                'referrer_promo_unit' => 'Percent',
                'referrer_promo_value' => 20,
                'referrer_promo_value_max' => 20000,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ),
        ));
        
        
    }
}