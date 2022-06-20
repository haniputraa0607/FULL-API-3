<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdCardNumberToHs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->string('id_card_number')->nullable()->after('blood_type');
            $table->string('file_contract')->nullable()->after('otp_increment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->dropColumn('id_card_number');
            $table->dropColumn('file_contract');
        });
    }
}
