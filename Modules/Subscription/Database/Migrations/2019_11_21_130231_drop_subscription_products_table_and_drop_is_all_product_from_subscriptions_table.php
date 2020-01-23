<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropSubscriptionProductsTableAndDropIsAllProductFromSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('is_all_product');
        });

        Schema::dropIfExists('subscription_products');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_all_product')->nullable()->after('subscription_minimal_transaction');
        });

        Schema::create('subscription_products', function (Blueprint $table) {
            $table->increments('id_subscription_product');
            $table->integer('id_subscription')->unsigned();
            $table->integer('id_product')->unsigned();
            $table->timestamps();

            $table->foreign('id_subscription', 'fk_subscriptions_subscriptions_products')->references('id_subscription')->on('subscriptions')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_product', 'fk_products_subscriptions_products')->references('id_product')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
        
    }
}
