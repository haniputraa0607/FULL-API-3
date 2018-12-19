<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeFieldCountLinkClickedPromotionContentsTable extends Migration
{
    public function up()
    {
        Schema::table('promotion_contents', function(Blueprint $table) {
            $table->integer('promotion_count_sms_link_clicked')->default(0)->after('promotion_count_sms_sent');
            $table->integer('promotion_count_push_link_clicked')->default(0)->after('promotion_count_push');
            $table->integer('promotion_count_inbox_link_clicked')->default(0)->after('promotion_count_inbox');
        });
    }

    public function down()
    {
        Schema::table('promotion_contents', function(Blueprint $table) {
            $table->dropColumn('promotion_count_sms_link_clicked');
            $table->dropColumn('promotion_count_push_link_clicked');
            $table->dropColumn('promotion_count_inbox_link_clicked');
        });
    } 
}
