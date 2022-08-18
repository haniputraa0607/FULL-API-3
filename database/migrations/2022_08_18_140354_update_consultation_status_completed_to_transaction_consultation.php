<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateConsultationStatusCompletedToTransactionConsultation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_consultations', function (Blueprint $table) {
            DB::statement('ALTER TABLE transaction_consultations CHANGE consultation_status consultation_status ENUM("soon", "ongoing", "done", "canceled", "missed", "completed") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
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
            DB::statement('ALTER TABLE transaction_consultations CHANGE consultation_status consultation_status ENUM("soon", "ongoing", "done", "canceled") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });
    }
}
