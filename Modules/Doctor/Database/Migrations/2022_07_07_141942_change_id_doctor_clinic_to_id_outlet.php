<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIdDoctorClinicToIdOutlet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `doctors` CHANGE COLUMN `id_doctor_clinic` `id_outlet` INTEGER NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("ALTER TABLE `doctors` CHANGE COLUMN `id_outlet` `id_doctor_clinic` VARCHAR(255) NOT NULL");
    }
}
