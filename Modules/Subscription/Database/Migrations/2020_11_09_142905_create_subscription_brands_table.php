<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_brands', function (Blueprint $table) {
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_brand');

            $table->foreign('id_subscription')->on('subscriptions')->references('id_subscription')->onDelete('cascade');
            $table->foreign('id_brand')->on('brands')->references('id_brand')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_brands');
    }
}
