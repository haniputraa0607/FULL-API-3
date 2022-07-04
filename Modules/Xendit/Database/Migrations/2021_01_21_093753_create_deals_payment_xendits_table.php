<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPaymentXenditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_payment_xendits', function (Blueprint $table) {
            $table->bigIncrements('id_deals_payment_xendit');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_deals_user');
            $table->string('order_id')->nullable();
            $table->string('xendit_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('business_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->string('amount')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('failure_code')->nullable();
            $table->string('status')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamps();

            $table->foreign('id_deals')->references('id_deals')->on('deals')->onDelete('cascade');
            $table->foreign('id_deals_user')->references('id_deals_user')->on('deals_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deals_payment_xendits');
    }
}
