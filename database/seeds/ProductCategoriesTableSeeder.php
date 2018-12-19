<?php

use Illuminate\Database\Seeder;

class ProductCategoriesTableSeeder extends Seeder
{
    public function run()
    {
        

        \DB::table('product_categories')->delete();
        
        \DB::table('product_categories')->insert(array (
            0 => 
            array (
                'id_product_category' => 1,
                'id_parent_category' => NULL,
                'product_category_order' => NULL,
                'product_category_name' => 'Skin',
                'product_category_description' => 'Produk perawatan kulit<br>',
                'product_category_photo' => 'img/product/category/711525226015.jpg',
                'created_at' => NULL,
                'updated_at' => '2018-06-07 15:21:16',
            ),
            1 => 
            array (
                'id_product_category' => 2,
                'id_parent_category' => 1,
                'product_category_order' => NULL,
                'product_category_name' => 'Men',
                'product_category_description' => '<p>Men can use it<br></p>',
                'product_category_photo' => 'img/product/category/9381524043198.jpg',
                'created_at' => NULL,
                'updated_at' => '2018-05-18 21:04:02',
            ),
            2 => 
            array (
                'id_product_category' => 3,
                'id_parent_category' => 1,
                'product_category_order' => NULL,
                'product_category_name' => 'Teen',
                'product_category_description' => '<p>Teen can use it<br></p>',
                'product_category_photo' => 'img/product/category/5861524043225.jpg',
                'created_at' => NULL,
                'updated_at' => '2018-04-18 21:20:25',
            ),
            3 => 
            array (
                'id_product_category' => 4,
                'id_parent_category' => 1,
                'product_category_order' => NULL,
                'product_category_name' => 'Women',
                'product_category_description' => '<b>Women can use it</b>',
                'product_category_photo' => 'img/product/category/3561524043249.jpg',
                'created_at' => NULL,
                'updated_at' => '2018-04-18 21:20:49',
            ),
            4 => 
            array (
                'id_product_category' => 5,
                'id_parent_category' => NULL,
                'product_category_order' => NULL,
                'product_category_name' => 'Hair Series',
                'product_category_description' => 'Untuk Perawatan Rambut anda<br><p><br></p>',
                'product_category_photo' => 'img/product/category/7511524043306.jpg',
                'created_at' => NULL,
                'updated_at' => '2018-06-07 15:20:57',
            ),
            5 => 
            array (
                'id_product_category' => 6,
                'id_parent_category' => NULL,
                'product_category_order' => NULL,
                'product_category_name' => 'Body Series',
                'product_category_description' => '<p>Produk untuk perawatan tubuh<br></p>',
                'product_category_photo' => 'img/product/category/5221524043319.jpg',
                'created_at' => '2018-04-18 16:46:22',
                'updated_at' => '2018-06-07 15:24:28',
            ),
        ));
        
        
    }
}