<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChannelPromotionSentsTable extends Migration
{
    public function up()
    {
        Schema::table('promotion_sents', function(Blueprint $table) {
            $table->char('channel_email', '1')->default('0')->after('send_at');
            $table->char('channel_sms', '1')->default('0')->after('channel_email');
            $table->char('channel_push', '1')->default('0')->after('channel_sms');
            $table->char('channel_inbox', '1')->default('0')->after('channel_push');
        });
    }

    public function down()
    {
        Schema::table('promotion_sents', function(Blueprint $table) {
            $table->dropColumn('channel_email');
            $table->dropColumn('channel_sms');
            $table->dropColumn('channel_push');
            $table->dropColumn('channel_inbox');
        });
    } 
}
