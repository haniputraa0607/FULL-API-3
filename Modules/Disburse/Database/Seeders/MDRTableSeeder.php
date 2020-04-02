<?php

namespace Modules\Disburse\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MDRTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('mdr')->delete();
        
        \DB::table('mdr')->insert(array (
            0 => 
            array (
                'id_mdr' => 1,
                'payment_name' => NULL,
                'mdr' => NULL,
                'percent_type' => 'Percent',
                'charged' => 'Outlet',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            )
        ));
        
        
    }
}