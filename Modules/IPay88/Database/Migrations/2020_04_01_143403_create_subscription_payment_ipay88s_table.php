<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionPaymentIpay88sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_payment_ipay88s', function (Blueprint $table) {
            $table->increments('id_subscription_payment_ipay88');
            $table->unsignedInteger('id_subscription');
            $table->unsignedInteger('id_subscription_user');
            $table->string('order_id');
            $table->boolean('from_user')->default(0);
            $table->boolean('from_backend')->default(0);
            $table->text('requery_response');
            $table->string('merchant_code',20);
            $table->integer('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('ref_no',20);
            $table->unsignedInteger('amount')->nullable();
            $table->string('currency',5);
            $table->string('remark',100)->nullable();
            $table->string('trans_id',30)->nullable();
            $table->string('auth_code',20)->nullable();
            $table->string('status',1);
            $table->string('err_desc',100)->nullable();
            $table->string('signature',100);
            $table->string('xfield1')->nullable();
            $table->timestamps();

            $table->foreign('id_subscription')->references('id_subscription')->on('subscriptions')->onDelete('cascade');
            $table->foreign('id_subscription_user')->references('id_subscription_user')->on('subscription_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_payment_ipay88s');
    }
}
