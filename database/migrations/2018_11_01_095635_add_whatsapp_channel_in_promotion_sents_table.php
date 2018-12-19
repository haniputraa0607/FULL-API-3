<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWhatsappChannelInPromotionSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // autocrms table
        Schema::table('promotion_sents', function (Blueprint $table) {
           $table->char('channel_whatsapp', 1)->default('0')->after('channel_inbox');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotion_sents', function (Blueprint $table) {
           $table->dropColumn('channel_whatsapp');
        });

    }
}
