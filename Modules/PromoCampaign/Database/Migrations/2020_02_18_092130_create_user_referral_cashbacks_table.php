<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserReferralCashbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_referral_cashbacks', function (Blueprint $table) {
            $table->increments('id_user_referral_cashback');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('cashback_earned');
            $table->timestamps();
            $table->foreign('id_user', 'fk_id_user_user_referral_cashbacks_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_referral_cashbacks');
    }
}
