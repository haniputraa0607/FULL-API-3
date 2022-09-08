<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionConsultationRecomendationsToNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultation_recomendations', function (Blueprint $table) {
            $table->string('usage_rules')->nullable()->change();
            $table->string('usage_rules_time')->nullable()->change();
            $table->string('usage_rules_additional_time')->nullable()->change();
            $table->text('treatment_description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_consultation_messages', function (Blueprint $table) {
            $table->string('usage_rules')->change();
            $table->string('usage_rules_time')->change();
            $table->string('usage_rules_additional_time')->change();
            $table->string('treatment_description')->change();
        });
    }
}
