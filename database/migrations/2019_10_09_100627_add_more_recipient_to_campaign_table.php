<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreRecipientToCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('campaign_email_more_recipient')->nullable()->after('campaign_email_receipient');
            $table->text('campaign_sms_more_recipient')->nullable()->after('campaign_sms_receipient');
            $table->text('campaign_push_more_recipient')->nullable()->after('campaign_push_receipient');
            $table->text('campaign_inbox_more_recipient')->nullable()->after('campaign_inbox_receipient');
            $table->boolean('campaign_complete')->nullable()->after('campaign_is_sent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('campaign_email_more_recipient');
            $table->dropColumn('campaign_sms_more_recipient');
            $table->dropColumn('campaign_push_more_recipient');
            $table->dropColumn('campaign_inbox_more_recipient');
            $table->dropColumn('campaign_complete');
        });
    }
}
