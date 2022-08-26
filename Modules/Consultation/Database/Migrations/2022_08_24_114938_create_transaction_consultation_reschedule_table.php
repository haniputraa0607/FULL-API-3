<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionConsultationRescheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_consultation_reschedules', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_consultation_reschedules');
            $table->integer('id_transaction');
            $table->integer('id_transaction_consultation');
            $table->integer('id_user');
            $table->integer('id_doctor');
            $table->time('schedule_start_time');
            $table->time('schedule_date_time');
            $table->enum('status', array('requested', 'approved', 'rejected', 'cancelled'));
            $table->integer('id_user_responder');
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
        Schema::connection('mysql2')->dropIfExists('log_shipper');
    }
}
