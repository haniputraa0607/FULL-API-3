<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSomeFieldNullableToLogPointsTable extends Migration
{
    public function up()
    {
        Schema::table('log_points', function (Blueprint $table) {
            $table->integer('voucher_price')->nullable(true)->change();
            $table->integer('membership_point_percentage')->nullable(true)->change();
        });
    }

    public function down()
    {
        Schema::table('log_points', function (Blueprint $table) {
            $table->integer('voucher_price')->nullable(false)->change();
            $table->integer('membership_point_percentage')->nullable(false)->change();
        });
    }
}
