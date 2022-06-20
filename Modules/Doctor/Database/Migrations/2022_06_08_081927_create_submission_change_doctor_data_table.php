<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubmissionChangeDoctorDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_change_doctor_data', function (Blueprint $table) {
            $table->bigIncrements('id_submission');
            $table->integer('id_doctor');
            $table->string('modified_column');
            $table->string('modified_value');
            $table->string('modified_reason');
            $table->string('approved_by');
            $table->boolean('is_approved');
            $table->boolean('is_rejected');
            $table->timestamp('approved_at');
            $table->timestamp('rejected_at');

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
        Schema::dropIfExists('submission_change_doctor_data');
    }
}
