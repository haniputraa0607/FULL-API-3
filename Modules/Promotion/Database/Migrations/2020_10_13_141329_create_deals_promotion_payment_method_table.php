<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPromotionPaymentMethodTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_promotion_payment_methods', function (Blueprint $table) {
            $table->increments('id_deals_promotion_payment_method');
            $table->unsignedInteger('id_deals');
            $table->string('payment_method');
            $table->timestamps();

            $table->foreign('id_deals', 'fk_deals_promotion_payment_methods_deals')->references('id_deals_promotion_template')->on('deals_promotion_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_promotion_payment_methods');
    }
}
