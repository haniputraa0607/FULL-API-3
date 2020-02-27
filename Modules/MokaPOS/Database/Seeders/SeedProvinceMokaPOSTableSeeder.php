<?php

namespace Modules\MokaPOS\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SeedProvinceMokaPOSTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('provinces')->delete();

        DB::table('provinces')->insert(array(
            0 =>
            array(
                'id_province' => 1,
                'province_name' => 'Bali',
            ),
            1 =>
            array(
                'id_province' => 2,
                'province_name' => 'Kepulauan Bangka Belitung',
            ),
            2 =>
            array(
                'id_province' => 3,
                'province_name' => 'Banten',
            ),
            3 =>
            array(
                'id_province' => 4,
                'province_name' => 'Bengkulu',
            ),
            4 =>
            array(
                'id_province' => 5,
                'province_name' => 'DI Yogyakarta',
            ),
            5 =>
            array(
                'id_province' => 6,
                'province_name' => 'DKI Jakarta',
            ),
            6 =>
            array(
                'id_province' => 7,
                'province_name' => 'Gorontalo',
            ),
            7 =>
            array(
                'id_province' => 8,
                'province_name' => 'Jambi',
            ),
            8 =>
            array(
                'id_province' => 9,
                'province_name' => 'Jawa Barat',
            ),
            9 =>
            array(
                'id_province' => 10,
                'province_name' => 'Jawa Tengah',
            ),
            10 =>
            array(
                'id_province' => 11,
                'province_name' => 'Jawa Timur',
            ),
            11 =>
            array(
                'id_province' => 12,
                'province_name' => 'Kalimantan Barat',
            ),
            12 =>
            array(
                'id_province' => 13,
                'province_name' => 'Kalimantan Selatan',
            ),
            13 =>
            array(
                'id_province' => 14,
                'province_name' => 'Kalimantan Tengah',
            ),
            14 =>
            array(
                'id_province' => 15,
                'province_name' => 'Kalimantan Timur',
            ),
            15 =>
            array(
                'id_province' => 16,
                'province_name' => 'Kalimantan Utara',
            ),
            16 =>
            array(
                'id_province' => 17,
                'province_name' => 'Kepulauan Riau',
            ),
            17 =>
            array(
                'id_province' => 18,
                'province_name' => 'Lampung',
            ),
            18 =>
            array(
                'id_province' => 19,
                'province_name' => 'Maluku',
            ),
            19 =>
            array(
                'id_province' => 20,
                'province_name' => 'Maluku Utara',
            ),
            20 =>
            array(
                'id_province' => 21,
                'province_name' => 'Aceh',
            ),
            21 =>
            array(
                'id_province' => 22,
                'province_name' => 'Nusa Tenggara Barat',
            ),
            22 =>
            array(
                'id_province' => 23,
                'province_name' => 'Nusa Tenggara Timur',
            ),
            23 =>
            array(
                'id_province' => 24,
                'province_name' => 'Papua',
            ),
            24 =>
            array(
                'id_province' => 25,
                'province_name' => 'Papua Barat',
            ),
            25 =>
            array(
                'id_province' => 26,
                'province_name' => 'Riau',
            ),
            26 =>
            array(
                'id_province' => 27,
                'province_name' => 'Sulawesi Barat',
            ),
            27 =>
            array(
                'id_province' => 28,
                'province_name' => 'Sulawesi Selatan',
            ),
            28 =>
            array(
                'id_province' => 29,
                'province_name' => 'Sulawesi Tengah',
            ),
            29 =>
            array(
                'id_province' => 30,
                'province_name' => 'Sulawesi Tenggara',
            ),
            30 =>
            array(
                'id_province' => 31,
                'province_name' => 'Sulawesi Utara',
            ),
            31 =>
            array(
                'id_province' => 32,
                'province_name' => 'Sumatera Barat',
            ),
            32 =>
            array(
                'id_province' => 33,
                'province_name' => 'Sumatera Selatan',
            ),
            33 =>
            array(
                'id_province' => 34,
                'province_name' => 'Sumatera Utara',
            ),
        ));
    }
}
