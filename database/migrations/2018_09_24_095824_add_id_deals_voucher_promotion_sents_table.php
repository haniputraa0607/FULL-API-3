<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdDealsVoucherPromotionSentsTable extends Migration
{
    public function up()
    {
        Schema::table('promotion_sents', function (Blueprint $table) {
			$table->string('id_deals_voucher')->nullable()->after('email_read');
        });
    }

    public function down()
    {
        Schema::table('promotion_sents', function (Blueprint $table) {
            $table->dropColumn('id_deals_voucher');
        });
    }
}
