<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutoresponsePaymentMethod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autoresponse_code_payment_methods', function (Blueprint $table) {
            $table->bigIncrements('id_autoresponse_code_payment_method');
            $table->unsignedInteger('id_autoresponse_code');
            $table->string('autoresponse_code_payment_method', 200);
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
        Schema::dropIfExists('autoresponse_code_payment_methods');
    }
}
