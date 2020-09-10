<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedirectComplexProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_complex_products', function (Blueprint $table) {
        	$table->increments('id_redirect_complex_product');
            $table->integer('id_redirect_complex_reference')->unsigned();
			$table->integer('id_product')->unsigned();
            $table->integer('qty');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('redirect_complex_products');
    }
}
