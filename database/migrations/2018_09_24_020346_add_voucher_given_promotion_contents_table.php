<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVoucherGivenPromotionContentsTable extends Migration
{
    public function up()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
			$table->integer('voucher_given')->nullable()->after('voucher_value');
        });
    }

    public function down()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
            $table->dropColumn('voucher_given');
        });
    }
}
