<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->decimal('charged_central', 5,2)->nullable()->after('custom_outlet_text');
            $table->decimal('charged_outlet', 5,2)->nullable()->after('custom_outlet_text');
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
            $table->drop('charged_central');
            $table->drop('charged_outlet');
        });
    }
}
