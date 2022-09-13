<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionConsultationReschedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('transaction_consultation_reschedules');
        
        Schema::create('transaction_consultation_reschedules', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_consultation_reschedules');
            $table->unsignedInteger('id_transaction_consultation');
            $table->unsignedInteger('id_doctor');
            $table->unsignedInteger('id_user');
            $table->date('old_schedule_date');
            $table->time('old_schedule_start_time');
            $table->time('old_schedule_end_time');
            $table->date('new_schedule_date');
            $table->time('new_schedule_start_time');
            $table->time('new_schedule_end_time');
            $table->unsignedInteger('id_user_modifier');
            $table->enum('user_modifier_type', array('Customer','Admin'));
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
        Schema::dropIfExists('transaction_consultation_reschedules');
    }
}
