<?php

namespace Modules\PointInjection\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class AddFeaturePointInjectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        \DB::table('features')->insert(array(
            0 =>
            array(
                'id_feature' => 149,
                'feature_type' => 'List',
                'feature_module' => 'Point Injection',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 =>
            array(
                'id_feature' => 150,
                'feature_type' => 'Create',
                'feature_module' => 'Point Injection',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            2 =>
            array(
                'id_feature' => 151,
                'feature_type' => 'Update',
                'feature_module' => 'Point Injection',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            3 =>
            array(
                'id_feature' => 152,
                'feature_type' => 'Delete',
                'feature_module' => 'Point Injection',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            4 =>
            array(
                'id_feature' => 153,
                'feature_type' => 'Detail',
                'feature_module' => 'Point Injection',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
        ));
    }
}
