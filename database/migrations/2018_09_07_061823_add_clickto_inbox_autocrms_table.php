<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClicktoInboxAutocrmsTable extends Migration
{
    public function up()
    {
        Schema::table('autocrms', function (Blueprint $table) {
			$table->string('autocrm_inbox_clickto', 191)->nullable()->after('autocrm_inbox_content');
			$table->string('autocrm_inbox_link',255)->nullable()->after('autocrm_inbox_clickto');
			$table->string('autocrm_inbox_id_reference', 20)->nullable()->after('autocrm_inbox_link');
        });
    }

    public function down()
    {
        Schema::table('autocrms', function (Blueprint $table) {
            $table->dropColumn('autocrm_inbox_clickto');
            $table->dropColumn('autocrm_inbox_link');
            $table->dropColumn('autocrm_inbox_id_reference');
        });
    }
}
