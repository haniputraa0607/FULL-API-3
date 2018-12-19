<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVoucherActiveAtToDealsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->dateTime('voucher_active_at')->comment('deals subscription voucher active timestamp')->nullable()->after('paid_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deals_users', function (Blueprint $table) {
            $table->dropColumn('voucher_active_at');
        });
    }
}
