<?php

use Illuminate\Database\Seeder;

class UserAddressesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('user_addresses')->delete();
        
        \DB::table('user_addresses')->insert(array (
            0 => 
            array (
                'id_user_address' => 1,
                'name' => 'Ivan',
                'phone' => '08985005660',
                'id_user' => 1,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78111',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => '2018-07-03 09:16:21',
            ),
            1 => 
            array (
                'id_user_address' => 2,
                'name' => 'Hani',
                'phone' => '085866937804',
                'id_user' => 3,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78112',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'id_user_address' => 3,
                'name' => 'Guntur',
                'phone' => '0811255501',
                'id_user' => 4,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78113',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            3 => 
            array (
                'id_user_address' => 4,
                'name' => 'Heru',
                'phone' => '083847090002',
                'id_user' => 5,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78114',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            4 => 
            array (
                'id_user_address' => 5,
                'name' => 'Tri',
                'phone' => '089674657270',
                'id_user' => 6,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78115',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            5 => 
            array (
                'id_user_address' => 6,
                'name' => 'Adi',
                'phone' => '082251401717',
                'id_user' => 7,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78116',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            6 => 
            array (
                'id_user_address' => 7,
                'name' => 'Niki',
                'phone' => '081329649882',
                'id_user' => 8,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78117',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            7 => 
            array (
                'id_user_address' => 8,
                'name' => 'Wahyu',
                'phone' => '082225501351',
                'id_user' => 9,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78118',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            8 => 
            array (
                'id_user_address' => 9,
                'name' => 'Denis',
                'phone' => '081379100375',
                'id_user' => 10,
                'id_city' => 501,
                'address' => 'Jalan Garuda',
                'postal_code' => '78119',
                'description' => 'Lorem',
                'primary' => '1',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
        ));
        
        
    }
}