<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMokaOutletTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moka_outlets', function (Blueprint $table) {
            $table->bigInteger('id_moka_outlet');
            $table->unsignedInteger('id_outlet');
            $table->unsignedBigInteger('id_moka_account');
            $table->timestamps();

            $table->foreign('id_moka_account', 'fk_moka_outlets_moka_accounts')->references('id_moka_account')->on('moka_accounts')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('id_outlet', 'fk_moka_outlets_outlets')->references('id_outlet')->on('outlets')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moka_outlets');
    }
}
