<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhotoToOutlets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('outlet_image_cover')->nullable()->after('outlet_status');
            $table->string('outlet_image_logo_portrait')->nullable()->after('outlet_status');
            $table->string('outlet_image_logo_landscape')->nullable()->after('outlet_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('outlet_image');
            $table->dropColumn('outlet_image_logo_portrait');
            $table->dropColumn('outlet_image_logo_landscape');
        });
    }
}
