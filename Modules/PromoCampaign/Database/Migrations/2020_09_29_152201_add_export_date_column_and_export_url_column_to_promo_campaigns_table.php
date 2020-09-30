<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExportDateColumnAndExportUrlColumnToPromoCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dateTime('export_date')->nullable()->after('min_basket_size');
        	$table->text('export_url', 65535)->nullable()->after('export_date');
            $table->enum('export_status', array('Running', 'Ready', 'Deleted'))->nullable()->after('export_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
        	$table->dropColumn('export_date');
            $table->dropColumn('export_url');
            $table->dropColumn('export_status');
        });
    }
}
