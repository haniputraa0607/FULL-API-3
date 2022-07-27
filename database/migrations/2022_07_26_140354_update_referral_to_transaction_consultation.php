<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateReferralToTransactionConsultation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->string('referral_code')->after('recipe_redemption_limit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
}
