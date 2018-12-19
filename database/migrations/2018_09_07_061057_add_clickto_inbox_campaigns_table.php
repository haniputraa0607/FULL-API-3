<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClicktoInboxCampaignsTable extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
			$table->string('campaign_inbox_clickto', 191)->nullable()->after('campaign_inbox_content');
			$table->string('campaign_inbox_link',255)->nullable()->after('campaign_inbox_clickto');
			$table->string('campaign_inbox_id_reference', 20)->nullable()->after('campaign_inbox_link');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('campaign_inbox_clickto');
            $table->dropColumn('campaign_inbox_link');
            $table->dropColumn('campaign_inbox_id_reference');
        });
    }
}
