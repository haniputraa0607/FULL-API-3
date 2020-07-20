<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisburseTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disburse_transactions', function (Blueprint $table) {
            $table->bigIncrements('id_disburse_transaction');
            $table->integer('id_disburse');
            $table->integer('id_transaction');
            $table->decimal('fee', 5,2)->nullable();
            $table->string('mdr_charged', 50)->nullable();
            $table->decimal('mdr', 5,2)->nullable();
            $table->decimal('mdr_central', 5,2)->nullable();
            $table->decimal('charged_point_central', 5,2)->nullable();
            $table->decimal('charged_point_outlet', 5,2)->nullable();
            $table->decimal('charged_promo_central', 5,2)->nullable();
            $table->decimal('charged_promo_outlet', 5,2)->nullable();
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
        Schema::dropIfExists('disburse_transactions');
    }
}
