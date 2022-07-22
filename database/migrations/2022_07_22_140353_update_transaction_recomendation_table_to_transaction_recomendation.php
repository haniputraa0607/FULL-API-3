<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionRecomendationTableToTransactionRecomendation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->string('usage_rules');
            $table->string('usage_rules_time');
            $table->string('usage_rules_additional_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->dropColumn('usage_rules');
            $table->dropColumn('usage_rule_time');
            $table->dropColumn('usage_rule_additional_time');
        });
    }
}
