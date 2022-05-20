<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromotionStatusToUserInbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->smallInteger('inboxes_promotion_status')->nullable()->default(0)->after('inboxes_send_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->dropColumn('inboxes_promotion_status');
        });
    }
}
