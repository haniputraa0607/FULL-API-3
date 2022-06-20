<?php

namespace Modules\UserRating\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class RatingOptionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('rating_options')->delete();
        
        \DB::table('rating_options')->insert(array (
            0 => 
            array (
                'id_rating_option'=>1,
                'rule_operator'=>'>',
                'value'=> 3,
                'question' => 'Apa yang menurutmu OK?',
                'options' => 'Kondisi barang,Ketepatan,Kecepatan Pesanan',
                'order' => 1
            ),
            1 => 
            array (
                'id_rating_option'=>2,
                'rule_operator'=>'=',
                'value'=> 3,
                'question' => 'Apa yang dapat kami tingkatkan?',
                'options' => 'Rasa,Harga,Kecepatan,Pelayanan',
                'order' => 2
            ),
            2 => 
            array (
                'id_rating_option'=>3,
                'rule_operator'=>'<',
                'value'=> 3,
                'question' => 'Apa yang tidak berkenan?',
                'options' => 'Kondisi barang,Ketepatan,Kecepatan Pesanan',
                'order' => 3
            ),
        ));
    }
}
