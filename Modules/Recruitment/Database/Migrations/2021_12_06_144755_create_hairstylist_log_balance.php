<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistLogBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_log_balances', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_log_balance');
            $table->unsignedInteger('id_user_hair_stylist');
            $table->integer('balance')->default(0);
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->integer('id_reference')->nullable();
            $table->string('source', 191)->nullable();
            $table->text('enc');
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
        Schema::dropIfExists('hairstylist_log_balances');
    }
}
