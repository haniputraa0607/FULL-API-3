<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletApiKeySecrets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_api_key_secrets', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_api_key_secrets');
            $table->integer('id_outlet')->unique();
            $table->string('api_key', 255);
            $table->string('api_secret', 255);
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
        Schema::dropIfExists('outlet_api_key_secrets');
    }
}
