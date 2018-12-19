<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClicktoInboxPromotionContentsTable extends Migration
{
    public function up()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
			$table->string('promotion_inbox_clickto', 191)->nullable()->after('promotion_inbox_content');
			$table->string('promotion_inbox_link',255)->nullable()->after('promotion_inbox_clickto');
			$table->string('promotion_inbox_id_reference', 20)->nullable()->after('promotion_inbox_link');
        });
    }

    public function down()
    {
        Schema::table('promotion_contents', function (Blueprint $table) {
            $table->dropColumn('promotion_inbox_clickto');
            $table->dropColumn('promotion_inbox_link');
            $table->dropColumn('promotion_inbox_id_reference');
        });
    }
}
