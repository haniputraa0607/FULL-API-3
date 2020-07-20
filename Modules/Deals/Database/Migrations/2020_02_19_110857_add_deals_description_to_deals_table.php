<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDealsDescriptionToDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	if (!Schema::hasColumn('deals', 'deals_description'))
        {
	        Schema::table('deals', function (Blueprint $table) {
	        	$table->text('deals_description')->nullable()->default(NULL)->after('deals_image');
	        });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals', function (Blueprint $table) {
        	$table->dropColumn('deals_description');
        });
    }
}
