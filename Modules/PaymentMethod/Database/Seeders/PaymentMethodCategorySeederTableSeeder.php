<?php

namespace Modules\PaymentMethod\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodCategorySeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        \DB::table('payment_method_categories')->insert(array(
            0 => ['payment_method_category_name' => 'Cash'],
            1 => ['payment_method_category_name' => 'E-Wallet']
        ));
    }
}
