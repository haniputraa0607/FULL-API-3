<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisburseOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disburse_outlet', function (Blueprint $table) {
            $table->bigIncrements('id_disburse_outlet');
            $table->integer('id_disburse');
            $table->integer('id_outlet')->nullable();
            $table->decimal('disburse_nominal', 30, 4)->default(0);
            $table->decimal('total_income_central', 30, 4)->default(0);
            $table->decimal('total_expense_central', 30, 4)->default(0);
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
        Schema::dropIfExists('disburse_outlet');
    }
}
