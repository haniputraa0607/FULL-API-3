<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingToFeaturedDealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('featured_deals', function (Blueprint $table) {
        	$table->index('start_date');
        	$table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('featured_deals', function (Blueprint $table) {
        	$table->dropIndex(['start_date']);
        	$table->dropIndex(['end_date']);
        });
    }
}
