<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id_subscription');
            $table->string('subscription_title', 45);
            $table->string('subscription_sub_title')->nullable();
            $table->string('subscription_image', 200);
            $table->dateTime('subscription_start');
            $table->dateTime('subscription_end');
            $table->dateTime('subscription_publish_start');
            $table->dateTime('subscription_publish_end');
            $table->integer('subscription_price_point')->nullable();
            $table->float('subscription_price_cash')->nullable();
            $table->text('subscription_description');
            $table->text('subscription_term');
            $table->text('subscription_how_to_use');
            $table->integer('subscription_bought')->nullable();
            $table->integer('subscription_total')->nullable();
            $table->integer('subscription_day_valid');
            $table->integer('subscription_voucher_total')->nullable();
            $table->integer('subscription_voucher_nominal');
            $table->integer('subscription_minimal_transaction')->nullable();
            $table->boolean('is_all_product')->nullable();
            $table->boolean('is_all_outlet')->nullable();
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
        Schema::dropIfExists('subscriptions');
    }
}
