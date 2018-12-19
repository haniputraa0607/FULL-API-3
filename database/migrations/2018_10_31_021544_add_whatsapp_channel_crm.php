<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWhatsappChannelCrm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // autocrms table
        Schema::table('autocrms', function (Blueprint $table) {
           $table->char('autocrm_whatsapp_toogle', 1)->default('0')->after('autocrm_inbox_toogle');
        });

        //campaigns table
        Schema::table('campaigns', function (Blueprint $table) {
           $table->enum('campaign_media_whatsapp', ['Yes', 'No'])->default('No')->after('campaign_media_inbox');
           $table->integer('campaign_whatsapp_count_all')->default(0)->after('campaign_inbox_count');
           $table->integer('campaign_whatsapp_count_queue')->default(0)->after('campaign_whatsapp_count_all');
           $table->integer('campaign_whatsapp_count_sent')->default(0)->after('campaign_whatsapp_count_queue');
           $table->text('campaign_whatsapp_receipient')->nullable()->after('campaign_inbox_receipient');
        });

        //promotions table
        Schema::table('promotion_contents', function (Blueprint $table) {
           $table->char('promotion_channel_whatsapp', 1)->default('0')->after('promotion_channel_inbox');
           $table->integer('promotion_count_whatsapp')->default(0)->after('promotion_count_inbox');
           $table->integer('promotion_count_whatsapp_link_clicked')->default(0)->after('promotion_count_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrms', function (Blueprint $table) {
           $table->dropColumn('autocrm_whatsapp_toogle');
        });

        Schema::table('campaigns', function (Blueprint $table) {
           $table->dropColumn('campaign_media_whatsapp');
           $table->dropColumn('campaign_whatsapp_count');
           $table->dropColumn('campaign_whatsapp_receipient');
        });

        Schema::table('promotion_contents', function (Blueprint $table) {
            $table->dropColumn('promotion_channel_whatsapp');
            $table->dropColumn('promotion_count_whatsapp');
            $table->dropColumn('promotion_count_whatsapp_link_clicked');
         });
    }
}
