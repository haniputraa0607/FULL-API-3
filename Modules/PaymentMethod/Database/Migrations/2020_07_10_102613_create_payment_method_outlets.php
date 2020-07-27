<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentMethodOutlets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_method_outlets', function (Blueprint $table) {
            $table->bigIncrements('id_payment_method_outlet');
            $table->unsignedInteger('id_payment_method');
            $table->unsignedInteger('id_outlet');
            $table->enum('status', ['Enable', 'Disable']);
            $table->timestamps();

            $table->foreign('id_payment_method')->references('id_payment_method')->on('payment_methods')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade')->onUpdate('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_method_outlets');
    }
}
