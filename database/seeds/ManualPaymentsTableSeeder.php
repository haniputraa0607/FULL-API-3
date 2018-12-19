<?php

use Illuminate\Database\Seeder;

class ManualPaymentsTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('manual_payments')->delete();
        
        \DB::table('manual_payments')->insert(array (
            0 => 
            array (
                'id_manual_payment' => 5,
                'is_virtual_account' => '1',
                'manual_payment_name' => 'BCA',
                'manual_payment_logo' => 'img/transaction/manual-payment/6431527651211.jpg',
                'account_number' => '087951968465',
                'account_name' => 'Guntur Saputro',
                'created_at' => '2018-05-30 10:33:31',
                'updated_at' => '2018-07-13 08:56:18',
            ),
        ));
        
        
    }
}