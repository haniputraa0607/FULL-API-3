<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductStockStatusUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_stock_status_updates', function (Blueprint $table) {
            $table->increments('id_product_stock_status_update');
            $table->dateTime('date_time');
            $table->unsignedInteger('id_user_outlet')->nullable();
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_product');
            $table->enum('new_status',['Available','Sold Out']);
            $table->timestamps();

            $table->foreign('id_user_outlet')->references('id_user_outlet')->on('user_outlets')->onDelete('set null');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('id_product')->references('id_product')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_stock_status_updates');
    }
}
