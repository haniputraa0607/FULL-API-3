<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistIncomeDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_income_details', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_income_detail');
            $table->unsignedBigInteger('id_hairstylist_income');
            $table->string('source');
            $table->string('reference');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->foreign('id_hairstylist_income')->on('hairstylist_incomes')->references('id_hairstylist_income')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_income_details');
    }
}
