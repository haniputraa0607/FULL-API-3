<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctorUpdateData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_update_datas', function (Blueprint $table) {
            $table->bigIncrements('id_doctor_update_data');
            $table->integer('id_doctor')->unsigned()->index();
            $table->unsignedInteger('approve_by')->nullable();
            $table->mediumText('field');
            $table->mediumText('new_value');
            $table->text('notes')->nullable();
            $table->datetime('approve_at')->nullable();
            $table->datetime('reject_at')->nullable();
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
        Schema::dropIfExists('doctor_update_datas');
    }
}
