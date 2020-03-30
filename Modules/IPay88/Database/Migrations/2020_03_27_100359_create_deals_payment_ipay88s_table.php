<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDealsPaymentIpay88sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deals_payment_ipay88s', function (Blueprint $table) {
            $table->increments('id_deals_payment_ipay88');
            $table->unsignedInteger('id_deals');
            $table->unsignedInteger('id_deals_user');
            $table->string('order_id');
            $table->boolean('from_user')->default(0);
            $table->boolean('from_backend')->default(0);
            $table->text('requery_response');
            $table->string('merchant_code',20);
            $table->integer('payment_id')->nullable();
            $table->string('ref_no',20);
            $table->decimal('amount',15,2);
            $table->string('currency',5);
            $table->string('remark',100)->nullable();
            $table->string('trans_id',30)->nullable();
            $table->string('auth_code',20)->nullable();
            $table->string('status',1);
            $table->string('err_desc',100)->nullable();
            $table->string('signature',100);
            $table->string('xfield1')->nullable();
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
        Schema::dropIfExists('deals_payment_ipay88s');
    }
}
