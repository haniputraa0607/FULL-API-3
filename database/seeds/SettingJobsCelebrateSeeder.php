<?php

use Illuminate\Database\Seeder;

class SettingJobsCelebrateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('settings')->insert(array (
            0 => 
            array (
                'key' => 'jobs_list',
                'value_text' => '["Petani","Nelayan","Guru","Penjahit","Pengacara"]',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 => 
            array (
                'key' => 'celebrate_list',
                'value_text' => '["Idul Fitri","Idul Adha","Natal","Nyepi","Kliwonan","Weekend"]',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            )
        ));
    }
}
