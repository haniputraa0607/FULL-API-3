<?php

use Illuminate\Database\Seeder;

class ManualPaymentMethodsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('manual_payment_methods')->delete();
        
        \DB::table('manual_payment_methods')->insert(array (
            0 => 
            array (
                'id_manual_payment_method' => 25,
                'id_manual_payment' => 5,
                'payment_method_name' => 'KLIK BCA',
                'created_at' => '2018-07-13 08:52:41',
                'updated_at' => '2018-07-13 08:55:16',
            ),
        ));
        
        
    }
}