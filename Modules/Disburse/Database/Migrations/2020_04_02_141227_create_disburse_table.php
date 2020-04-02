<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisburseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disburse', function (Blueprint $table) {
            $table->bigIncrements('id_disburse');
            $table->integer('disburse_nominal')->default(0);
            $table->enum('disburse_status', ['Success', 'Fail'])->nullable();
            $table->string('beneficiary_bank_name', 191)->nullable();
            $table->string('beneficiary_account_number', 191)->nullable();
            $table->string('recipient_name', 191)->nullable();
            $table->longText('request')->nullable();
            $table->longText('response')->nullable();
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
        Schema::dropIfExists('disburse');
    }
}
