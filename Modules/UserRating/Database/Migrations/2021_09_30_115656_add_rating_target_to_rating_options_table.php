<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRatingTargetToRatingOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rating_options', function (Blueprint $table) {
        	$table->enum('rating_target', ['hairstylist', 'outlet'])->nullable()->after('options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rating_options', function (Blueprint $table) {
        	$table->dropColumn('rating_target');
        });
    }
}
