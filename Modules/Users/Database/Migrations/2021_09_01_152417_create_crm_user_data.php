<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmUserData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_user_data', function (Blueprint $table) {
            $table->bigIncrements('crm_user_data_id');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('name',255)->nullable();
            $table->string('phone',255)->nullable();
            $table->string('email',255)->nullable();
            $table->string('recency',255)->nullable();
            $table->string('frequency',255)->nullable();
            $table->float('monetary_value', 99, 2)->nullable();
            $table->string('r_quartile',255)->nullable();
            $table->string('f_quartile',255)->nullable();
            $table->string('m_quartile',255)->nullable();
            $table->string('RFMScore',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_user_data');
    }
}
