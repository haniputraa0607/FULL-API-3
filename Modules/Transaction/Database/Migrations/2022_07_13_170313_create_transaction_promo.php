<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPromo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_promos', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_promo');
            $table->unsignedInteger('id_transaction')->nullable();
            $table->string('promo_name', 200);
            $table->enum('promo_type', array('Deals','Promo Campaign'));
            $table->unsignedInteger('id_deals_user')->nullable();
            $table->unsignedInteger('id_promo_campaign_promo_code')->nullable();
            $table->integer('discount_value');
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
        Schema::dropIfExists('transaction_promos');
    }
}
