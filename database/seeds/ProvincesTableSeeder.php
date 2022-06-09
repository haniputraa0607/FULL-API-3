<?php

use Illuminate\Database\Seeder;

class ProvincesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('provinces')->delete();
        
        \DB::table('provinces')->insert(
            array (
                0 =>
                    array (
                        'id_province' => 1,
                        'province_name' => 'Bali',
                        'time_zone_utc' => NULL,
                    ),
                1 =>
                    array (
                        'id_province' => 2,
                        'province_name' => 'Bangka Belitung',
                        'time_zone_utc' => NULL,
                    ),
                2 =>
                    array (
                        'id_province' => 3,
                        'province_name' => 'Banten',
                        'time_zone_utc' => NULL,
                    ),
                3 =>
                    array (
                        'id_province' => 4,
                        'province_name' => 'Bengkulu',
                        'time_zone_utc' => NULL,
                    ),
                4 =>
                    array (
                        'id_province' => 5,
                        'province_name' => 'DI Yogyakarta',
                        'time_zone_utc' => NULL,
                    ),
                5 =>
                    array (
                        'id_province' => 6,
                        'province_name' => 'DKI Jakarta',
                        'time_zone_utc' => NULL,
                    ),
                6 =>
                    array (
                        'id_province' => 7,
                        'province_name' => 'Gorontalo',
                        'time_zone_utc' => NULL,
                    ),
                7 =>
                    array (
                        'id_province' => 8,
                        'province_name' => 'Jambi',
                        'time_zone_utc' => NULL,
                    ),
                8 =>
                    array (
                        'id_province' => 9,
                        'province_name' => 'Jawa Barat',
                        'time_zone_utc' => NULL,
                    ),
                9 =>
                    array (
                        'id_province' => 10,
                        'province_name' => 'Jawa Tengah',
                        'time_zone_utc' => NULL,
                    ),
                10 =>
                    array (
                        'id_province' => 11,
                        'province_name' => 'Jawa Timur',
                        'time_zone_utc' => NULL,
                    ),
                11 =>
                    array (
                        'id_province' => 12,
                        'province_name' => 'Kalimantan Barat',
                        'time_zone_utc' => NULL,
                    ),
                12 =>
                    array (
                        'id_province' => 13,
                        'province_name' => 'Kalimantan Selatan',
                        'time_zone_utc' => NULL,
                    ),
                13 =>
                    array (
                        'id_province' => 14,
                        'province_name' => 'Kalimantan Tengah',
                        'time_zone_utc' => NULL,
                    ),
                14 =>
                    array (
                        'id_province' => 15,
                        'province_name' => 'Kalimantan Timur',
                        'time_zone_utc' => NULL,
                    ),
                15 =>
                    array (
                        'id_province' => 16,
                        'province_name' => 'Kalimantan Utara',
                        'time_zone_utc' => NULL,
                    ),
                16 =>
                    array (
                        'id_province' => 17,
                        'province_name' => 'Kepulauan Riau',
                        'time_zone_utc' => NULL,
                    ),
                17 =>
                    array (
                        'id_province' => 18,
                        'province_name' => 'Lampung',
                        'time_zone_utc' => NULL,
                    ),
                18 =>
                    array (
                        'id_province' => 19,
                        'province_name' => 'Maluku Utara',
                        'time_zone_utc' => NULL,
                    ),
                19 =>
                    array (
                        'id_province' => 20,
                        'province_name' => 'Maluku',
                        'time_zone_utc' => NULL,
                    ),
                20 =>
                    array (
                        'id_province' => 21,
                        'province_name' => 'Aceh',
                        'time_zone_utc' => NULL,
                    ),
                21 =>
                    array (
                        'id_province' => 22,
                        'province_name' => 'Nusa Tenggara Barat',
                        'time_zone_utc' => NULL,
                    ),
                22 =>
                    array (
                        'id_province' => 23,
                        'province_name' => 'Nusa Tenggara Timur',
                        'time_zone_utc' => NULL,
                    ),
                23 =>
                    array (
                        'id_province' => 24,
                        'province_name' => 'Papua Barat',
                        'time_zone_utc' => NULL,
                    ),
                24 =>
                    array (
                        'id_province' => 25,
                        'province_name' => 'Papua',
                        'time_zone_utc' => NULL,
                    ),
                25 =>
                    array (
                        'id_province' => 26,
                        'province_name' => 'Riau',
                        'time_zone_utc' => NULL,
                    ),
                26 =>
                    array (
                        'id_province' => 27,
                        'province_name' => 'Sulawesi Barat',
                        'time_zone_utc' => NULL,
                    ),
                27 =>
                    array (
                        'id_province' => 28,
                        'province_name' => 'Sulawesi Selatan',
                        'time_zone_utc' => NULL,
                    ),
                28 =>
                    array (
                        'id_province' => 29,
                        'province_name' => 'Sulawesi Tengah',
                        'time_zone_utc' => NULL,
                    ),
                29 =>
                    array (
                        'id_province' => 30,
                        'province_name' => 'Sulawesi Tenggara',
                        'time_zone_utc' => NULL,
                    ),
                30 =>
                    array (
                        'id_province' => 31,
                        'province_name' => 'Sulawesi Utara',
                        'time_zone_utc' => NULL,
                    ),
                31 =>
                    array (
                        'id_province' => 32,
                        'province_name' => 'Sumatera Barat',
                        'time_zone_utc' => NULL,
                    ),
                32 =>
                    array (
                        'id_province' => 33,
                        'province_name' => 'Sumatera Selatan',
                        'time_zone_utc' => NULL,
                    ),
                33 =>
                    array (
                        'id_province' => 34,
                        'province_name' => 'Sumatera Utara',
                        'time_zone_utc' => NULL,
                    ),
            )
        );
    }
}