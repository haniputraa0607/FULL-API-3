<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDiseaseTreatmentToTransactionConsultationsTable extends Migration 
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            $table->string('disease_complaint')->after('consultation_status')->nullable();
            $table->string('disease_analysis')->after('disease_complaint')->nullable();
            $table->string('treatment_recomendation')->after('disease_analysis')->nullable();
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
            $table->dropColumn('disease_complaint');
            $table->dropColumn('disease_analysis');
            $table->dropColumn('treatment_recomendation');
        });
    }
}
