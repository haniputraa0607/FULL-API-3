<?php

use Illuminate\Database\Seeder;

class ProductPricesTableSeeder extends Seeder
{
    public function run()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        \DB::table('product_prices')->truncate();
        // data feature
        $dataPrice = [
            [
				'id_product'    => 1,
				'id_outlet'     => 1,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 1,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 1,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 1,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 1,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 1,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 1,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 1,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 1,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 1,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 1,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 1,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 2,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 2,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 2,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 2,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 2,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 2,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 2,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 2,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 2,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 2,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 2,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 2,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 3,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 3,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 3,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 3,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 3,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 3,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 3,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 3,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 3,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 3,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 3,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 3,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 4,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 4,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 4,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 4,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 4,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 4,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 4,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 4,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 4,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 4,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 4,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 4,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 5,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 5,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 5,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 5,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 5,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 5,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 5,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 5,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 5,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 5,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 5,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 5,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 6,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 6,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 6,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 6,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 6,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 6,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 6,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 6,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 6,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 6,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 6,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 6,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 7,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 7,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 7,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 7,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 7,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 7,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 7,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 7,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 7,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 7,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 7,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 7,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 8,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 8,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 8,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 8,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 8,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 8,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 8,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 8,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 8,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 8,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 8,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 8,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 9,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 9,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 9,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 9,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 9,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 9,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 9,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 9,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 9,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 9,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 9,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 9,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 10,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 10,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 10,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 10,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 10,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 10,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 10,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 10,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 10,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 10,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 10,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 10,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 1,
				'id_outlet'     => 11,
				'product_price'	=> 80000,
            ],
            [
				'id_product'    => 2,
				'id_outlet'     => 11,
				'product_price'	=> 30000,
            ],
            [
				'id_product'    => 3,
				'id_outlet'     => 11,
				'product_price'	=> 25000,
            ],
            [
				'id_product'    => 4,
				'id_outlet'     => 11,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 5,
				'id_outlet'     => 11,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 6,
				'id_outlet'     => 11,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 7,
				'id_outlet'     => 11,
				'product_price'	=> 10000,
            ],
            [
				'id_product'    => 8,
				'id_outlet'     => 11,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 9,
				'id_outlet'     => 11,
				'product_price'	=> 100000,
            ],
            [
				'id_product'    => 10,
				'id_outlet'     => 11,
				'product_price'	=> 50000,
            ],
            [
				'id_product'    => 11,
				'id_outlet'     => 11,
				'product_price'	=> 70000,
            ],
            [
				'id_product'    => 12,
				'id_outlet'     => 11,
				'product_price'	=> 70000,
            ],
        ];

        // data feature with id
        foreach ($dataPrice as $key=>$val) {
            $dataPrice[$key]['id_product_price'] = $key+1;
            $dataPrice[$key]['product_visibility'] = 'Visible';
        }

        \DB::table('product_prices')->delete();
        
        \DB::table('product_prices')->insert($dataPrice);
    }
}
