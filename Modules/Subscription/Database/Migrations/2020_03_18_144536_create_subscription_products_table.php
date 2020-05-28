<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_products', function (Blueprint $table) {
            $table->increments('id_subscription_product');
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_product')->nullable();
            $table->unsignedInteger('id_product_category')->nullable();
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscription_product_subscription')
            	  ->references('id_subscription')
            	  ->on('subscriptions')
            	  ->onUpdate('CASCADE')
            	  ->onDelete('CASCADE');

            $table->foreign('id_product', 'fk_subscription_product_product')
            	  ->references('id_product')
            	  ->on('products')
            	  ->onUpdate('CASCADE')
            	  ->onDelete('CASCADE')
            	  ->nullable();

            $table->foreign('id_product_category', 'fk_subscription_product_product_category')
            	  ->references('id_product_category')
            	  ->on('product_categories')
            	  ->onUpdate('CASCADE')
            	  ->onDelete('CASCADE')
            	  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_products');
    }
}
