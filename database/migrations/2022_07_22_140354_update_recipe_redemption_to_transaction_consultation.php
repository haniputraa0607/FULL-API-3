<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateRecipeRedemptionToTransactionConsultation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->integer('recipe_redemption_limit')->after('treatment_recomendation');
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
            $table->dropColumn('recipe_redemption_limit');
        });
    }
}
