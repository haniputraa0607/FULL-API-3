<?php

use Illuminate\Database\Seeder;

class CourierTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('courier')->delete();
        
        \DB::table('courier')->insert(array (
            0 => 
            array (
                'id_courier' => 1,
                'short_name' => 'psc',
                'name' => 'Private Shipping Cou',
                'status' => 'Active',
                'courier_type' => 'Internal',
                'created_at' => '2017-12-20 08:57:41',
                'updated_at' => '2017-12-20 08:57:41',
            ),
            1 => 
            array (
                'id_courier' => 4,
                'short_name' => 'jne',
                'name' => 'Jalur Nugraha Ekakur',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            2 => 
            array (
                'id_courier' => 5,
                'short_name' => 'pos',
                'name' => 'POS Indonesia',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            3 => 
            array (
                'id_courier' => 6,
                'short_name' => 'tiki',
                'name' => 'Citra Van Titipan Ki',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            4 => 
            array (
                'id_courier' => 7,
                'short_name' => 'pcp',
                'name' => 'Priority Cargo and P',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            5 => 
            array (
                'id_courier' => 8,
                'short_name' => 'esl',
                'name' => 'Eka Sari Lorena',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            6 => 
            array (
                'id_courier' => 9,
                'short_name' => 'rpx',
                'name' => 'RPX Holding',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            7 => 
            array (
                'id_courier' => 10,
                'short_name' => 'pandu',
                'name' => 'Pandu Logistics',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            8 => 
            array (
                'id_courier' => 11,
                'short_name' => 'wahana',
                'name' => 'Wahana Prestasi Logi',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            9 => 
            array (
                'id_courier' => 12,
                'short_name' => 'sicepat',
                'name' => 'SiCepat Express',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            10 => 
            array (
                'id_courier' => 13,
                'short_name' => 'jnt',
                'name' => 'J&T Express',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            11 => 
            array (
                'id_courier' => 14,
                'short_name' => 'pahala',
                'name' => 'Pahala Kencana Expre',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            12 => 
            array (
                'id_courier' => 15,
                'short_name' => 'cahaya',
                'name' => 'Cahaya Logistik',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            13 => 
            array (
                'id_courier' => 16,
                'short_name' => 'sap',
                'name' => 'SAP Express',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            14 => 
            array (
                'id_courier' => 17,
                'short_name' => 'jet',
                'name' => 'JET Express',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            15 => 
            array (
                'id_courier' => 18,
                'short_name' => 'indah',
                'name' => 'Indah Logistic',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            16 => 
            array (
                'id_courier' => 19,
                'short_name' => 'slis',
                'name' => 'Solusi Ekspres',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            17 => 
            array (
                'id_courier' => 20,
                'short_name' => 'dse',
                'name' => '21 Express',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            18 => 
            array (
                'id_courier' => 21,
                'short_name' => 'first',
                'name' => 'First Logistics',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            19 => 
            array (
                'id_courier' => 22,
                'short_name' => 'ncs',
                'name' => 'Nusantara Card Semes',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
            20 => 
            array (
                'id_courier' => 23,
                'short_name' => 'star',
                'name' => 'Star Cargo',
                'status' => 'Active',
                'courier_type' => 'External',
                'created_at' => '2018-02-07 10:18:01',
                'updated_at' => '2018-02-07 10:18:01',
            ),
        ));
        
        
    }
}