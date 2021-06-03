<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPushClickAtToPromotionSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotion_sents', function (Blueprint $table) {
        	$table->datetime('push_click_at')->nullable()->after('email_read');
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
			$table->dropColumn('push_click_at');
        });
    }
}
