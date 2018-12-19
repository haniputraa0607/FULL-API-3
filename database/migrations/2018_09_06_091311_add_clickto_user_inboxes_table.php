<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClicktoUserInboxesTable extends Migration
{
    public function up()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
			$table->string('inboxes_clickto', 191)->after('inboxes_content');
			$table->string('inboxes_link',255)->nullable()->after('inboxes_clickto');
			$table->string('inboxes_id_reference', 20)->nullable()->after('inboxes_link');
        });
    }

    public function down()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->dropColumn('inboxes_clickto');
            $table->dropColumn('inboxes_link');
            $table->dropColumn('inboxes_id_reference');
        });
    }
}
