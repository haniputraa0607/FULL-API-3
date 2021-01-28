<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumImageDetailBundling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->string('image_detail')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundling', function (Blueprint $table) {
            $table->dropColumn('image_detail');
        });
    }
}
