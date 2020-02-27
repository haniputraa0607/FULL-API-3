<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMokaAccountBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moka_account_business', function (Blueprint $table) {
            $table->bigIncrements('id_moka_account_business');
            $table->unsignedBigInteger('id_moka_account');
            $table->bigInteger('id_moka_business');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->timestamps();

            $table->foreign('id_moka_account', 'fk_moka_account_business_moka_accounts')->references('id_moka_account')->on('moka_accounts')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moka_account_business');
    }
}
