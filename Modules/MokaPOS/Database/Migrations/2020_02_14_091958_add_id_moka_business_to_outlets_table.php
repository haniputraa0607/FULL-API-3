<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdMokaBusinessToOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->unsignedBigInteger('id_moka_account_business')->after('id_outlet');
            $table->bigInteger('id_moka_outlet')->after('id_outlet');

            $table->foreign('id_moka_account_business', 'fk_outlets_moka_account_business')->references('id_moka_account_business')->on('moka_account_business')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
        });
    }
}
