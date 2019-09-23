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
                'value_text' => '["Karyawan Swasta", "Mengurus Rumah Tangga", "Pegawai Negeri Sipil", "Pelajar / Mahasiswa", "Wirausaha / Profesional", "Belum / Tidak Bekerja", "Lainnya"]',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 => 
            array (
                'key' => 'celebrate_list',
                'value_text' => '["Idul Fitri", "Waisak", "Natal", "Imlek", "Galungan", "Lainnya"]',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            )
        ));
    }
}
