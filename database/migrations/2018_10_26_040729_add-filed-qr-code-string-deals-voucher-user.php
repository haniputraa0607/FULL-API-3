<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFiledQrCodeStringDealsVoucherUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deals_users', function (Blueprint $table) {
           $table->string('voucher_hash_code', 20)->nullable()->after('voucher_hash');
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
           $table->dropColumn('voucher_hash_code');
        });
    }
}
