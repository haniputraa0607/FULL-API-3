<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexToAllTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_versions', function (Blueprint $table) {
            $table->index(['app_type']);
        });
        Schema::table('autocrms', function (Blueprint $table) {
            $table->index(['autocrm_type']);
            $table->index(['autocrm_title']);
        });
        Schema::table('news', function (Blueprint $table) {
            $table->index(['news_slug']);
        });
        Schema::table('outlets', function (Blueprint $table) {
            $table->index(['beneficiary_account']);
            $table->index(['outlet_special_status']);
            $table->index(['outlet_different_price']);
            $table->index(['outlet_latitude']);
            $table->index(['notify_admin']);
            $table->index(['outlet_status']);
        });
        Schema::table('pivot_point_injections', function (Blueprint $table) {
            $table->index(['send_time']);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->index(['key']);
        });
        Schema::table('user_devices', function (Blueprint $table) {
            $table->index(['device_id']);
            $table->index(['device_token']);
            $table->index(['device_type']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->index(['level']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
