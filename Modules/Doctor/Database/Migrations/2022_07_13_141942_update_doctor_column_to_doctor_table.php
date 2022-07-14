<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDoctorColumnToDoctorTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->boolean('phone_verified')->after('doctor_phone');
            $table->string('id_card_number')->after('password');
            $table->integer('sms_increment')->after('doctor_session_price');
            $table->integer('total_rating')->after('sms_increment');
            $table->string('practice_experience')->after('doctor_service');
            $table->text('practice_experience_place')->after('practice_experience');
            $table->string('alumni')->after('practice_experience_place');
            $table->string('registration_certificate_number')->after('alumni');
            $table->string('practice_lisence_number')->after('registration_certificate_number');
            $table->boolean('schedule_toogle')->after('total_rating');
            $table->boolean('notification_toogle')->after('total_rating');
            $table->date('birthday')->change();
            $table->string('celebrate')->change();
            $table->text('address')->change();
        });

        DB::statement('ALTER TABLE doctors CHANGE gender gender ENUM("Male", "Female") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('doctor_email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('doctor_email')->after('doctor_phone');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('phone_verified');
            $table->dropColumn('id_card_number');
            $table->dropColumn('sms_increment');
            $table->dropColumn('total_rating');
            $table->dropColumn('practical_experience');
            $table->dropColumn('alumni');
            $table->dropColumn('registration_certificate_number');
            $table->dropColumn('practice_lisence_number');
            $table->dropColumn('schedule_toogle');
            $table->dropColumn('notification_toogle');
        });
    }
}
