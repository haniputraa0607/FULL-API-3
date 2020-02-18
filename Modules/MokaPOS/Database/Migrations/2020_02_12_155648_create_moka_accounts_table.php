<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMokaAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moka_accounts', function (Blueprint $table) {
            $table->bigIncrements('id_moka_account');
            $table->string('name');
            $table->text('desc');
            $table->string('application_id');
            $table->string('secret');
            $table->string('code');
            $table->string('redirect_url');
            $table->string('token');
            $table->string('refresh_token');
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
        Schema::dropIfExists('moka_accounts');
    }
}
