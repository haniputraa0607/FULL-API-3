<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealsTypeInDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_type deals_type ENUM('Deals', 'Hidden', 'Point', 'Spin') NOT NULL DEFAULT 'Deals'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
            DB::statement("ALTER TABLE deals CHANGE COLUMN deals_type deals_type ENUM('Deals', 'Hidden', 'Point') NOT NULL DEFAULT 'Deals'");
        });
    }
}
