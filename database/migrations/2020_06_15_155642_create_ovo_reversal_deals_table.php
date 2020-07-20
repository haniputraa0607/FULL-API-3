<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOvoReversalDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ovo_reversal_deals', function (Blueprint $table) {
            $table->increments('id_ovo_reversal_deals');
            $table->integer('id_deals_user')->unsigned();
            $table->integer('id_deals_payment_ovo')->unsigned();
            $table->datetime('date_push_to_pay');
            $table->text('request');
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
        Schema::dropIfExists('ovo_reversal_deals');
    }
}
