<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTrxProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_trx_products', function (Blueprint $table) {
            $table->increments('id_user_trx_product');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_product');
            $table->date('last_trx_date');
            $table->integer('product_qty');
            $table->timestamps();

            $table->foreign('id_user', 'fk_user_trx_products_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_user_trx_products_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_trx_products');
    }
}
