<?php

use Illuminate\Database\Seeder;

class FraudSettingsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('fraud_settings')->delete();
        
        \DB::table('fraud_settings')->insert(array (
            0 => 
            array (
                'parameter' => 'Number of transactions in 1 day for each customer',
                'parameter_detail' => 2,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 => 
            array (
                'parameter' => 'Number of transactions in 1 week for each customer',
                'parameter_detail' => 5,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            2 => 
            array (
                'parameter' => 'Customer login using a device ID that has been used by another customer',
                'parameter_detail' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            3 =>
                array (
                    'parameter' => 'Transaction between',
                    'parameter_detail' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            4 =>
                array (
                    'parameter' => 'Point user',
                    'parameter_detail' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            5 =>
                array (
                    'parameter' => 'Check promo code',
                    'parameter_detail' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            6 =>
                array (
                    'parameter' => 'Check referral',
                    'parameter_detail' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ),
            7 =>
                array (
                    'parameter' => 'Check referral user',
                    'parameter_detail' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                )
        ));
    }
}