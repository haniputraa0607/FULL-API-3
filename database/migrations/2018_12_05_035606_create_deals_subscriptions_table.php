<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_subscriptions', function (Blueprint $table) {
            $table->increments('id_deals_subscription');
            $table->integer('id_deals')->unsigned();
            $table->enum('promo_type', ['promoid','nominal','free item'])->default('promoid');
            $table->char('promo_value', 200)->comment('can be: promo id, nominal, or product id');
            $table->integer('total_voucher');
            $table->integer('voucher_start')->comment('x days after deals claimed');
            $table->integer('voucher_end')->comment('x days after deals claimed');
            $table->timestamps();

            $table->foreign('id_deals', 'fk_subscription_deals')->references('id_deals')->on('deals')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_subscriptions');
    }
}
