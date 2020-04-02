<?php

namespace Modules\Disburse\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class BankTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('bank_name')->delete();
        
        \DB::table('bank_name')->insert(array (
            0 => 
            array (
                'id_bank_name' => 1,
                'bank_code' => '441',
                'bank_name' => 'BANK BUKOPIN',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 2,
                'bank_code' => '014',
                'bank_name' => 'BANK CENTRAL ASIA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 3,
                'bank_code' => '022',
                'bank_name' => 'BANK CIMB NIAGA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 4,
                'bank_code' => '011',
                'bank_name' => 'BANK DANAMON INDONESIA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 5,
                'bank_code' => '008',
                'bank_name' => 'BANK MANDIRI ',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 6,
                'bank_code' => '426',
                'bank_name' => 'BANK MEGA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 7,
                'bank_code' => '009',
                'bank_name' => 'BANK NEGARA INDONESIA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 8,
                'bank_code' => '028',
                'bank_name' => 'BANK OCBC NISP',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 9,
                'bank_code' => '013',
                'bank_name' => 'BANK PERMATA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ),
            array (
                'id_bank_name' => 10,
                'bank_code' => '002',
                'bank_name' => 'BANK RAKYAT INDONESIA',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            )
        ));
        
        
    }
}