<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyCheckPromoCodeTables extends Migration
{
    public $set_schema_table = 'daily_check_promo_code';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->set_schema_table)) return;
        Schema::connection('mysql2')->create($this->set_schema_table, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id_daily_check_promo_code');
            $table->unsignedInteger('id_user');
            $table->string('device_id',255)->nullable();
            $table->string('promo_code',255)->nullable();
            $table->string('ip', 25)->nullable();
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
        Schema::connection('mysql2')->dropIfExists('daily_check_promo_code');
    }
}
