<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveTypeFieldToBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('type')->default('general')->after('position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->string('type')->default('general');
        });
    }
}
