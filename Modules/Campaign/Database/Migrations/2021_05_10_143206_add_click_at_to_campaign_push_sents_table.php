<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClickAtToCampaignPushSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_push_sents', function (Blueprint $table) {
        	$table->datetime('click_at')->nullable()->after('push_sent_send_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_push_sents', function (Blueprint $table) {
        	$table->dropColumn('click_at');
        });
    }
}
