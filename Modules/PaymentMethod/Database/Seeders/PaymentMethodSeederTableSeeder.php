<?php

namespace Modules\PaymentMethod\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        \DB::table('payment_methods')->insert(array(
            0 => [
                'id_payment_method_category' => 1,
                'payment_method_name' => 'Cash'
            ],
            1 => [
                'id_payment_method_category' => 2,
                'payment_method_name' => 'OVO'
            ],
            2 => [
                'id_payment_method_category' => 2,
                'payment_method_name' => 'GO-PAY'
            ],
            3 => [
                'id_payment_method_category' => 2,
                'payment_method_name' => 'Shopee Pay'
            ],
            4 => [
                'id_payment_method_category' => 2,
                'payment_method_name' => 'Dana'
            ],
        ));
    }
}
