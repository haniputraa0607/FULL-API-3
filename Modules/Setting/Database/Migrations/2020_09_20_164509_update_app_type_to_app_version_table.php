<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAppTypeToAppVersionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_versions', function (Blueprint $table) {
            DB::statement('ALTER TABLE app_versions CHANGE app_type app_type ENUM("Android", "IOS", "OutletApp", "DoctorAndroid", "DoctorIOS") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_versions', function (Blueprint $table) {
            DB::statement('ALTER TABLE app_versions CHANGE app_type app_type ENUM("Android", "IOS", "OutletApp") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });
    }
}
