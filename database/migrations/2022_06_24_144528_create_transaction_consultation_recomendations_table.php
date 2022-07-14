<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionConsultationRecomendationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_consultation_recomendation');
            $table->unsignedInteger('id_transaction_consultation');
            $table->unsignedInteger('id_product');
            $table->enum('product_type', array('Product','Drug'));
            $table->string('qty_product');
            $table->unsignedInteger('id_outlet');
            $table->string('treatment_description');
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
        Schema::dropIfExists('transaction_consultation_recomendations');
    }
}
