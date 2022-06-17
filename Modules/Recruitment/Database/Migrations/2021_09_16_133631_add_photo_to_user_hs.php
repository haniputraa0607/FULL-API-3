<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhotoToUserHs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->string('user_hair_stylist_photo')->nullable()->after('approve_by');
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
            $table->dropColumn('user_hair_stylist_photo');
        });
    }
}
