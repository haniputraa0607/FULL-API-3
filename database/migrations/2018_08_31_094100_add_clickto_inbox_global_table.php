<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClicktoInboxGlobalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inbox_globals', function (Blueprint $table) {
			$table->string('inbox_global_clickto', 191)->after('inbox_global_subject');
			$table->string('inbox_global_link',255)->after('inbox_global_clickto');
			$table->string('inbox_global_id_reference', 20)->after('inbox_global_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inbox_globals', function (Blueprint $table) {
            $table->dropColumn('inbox_global_clickto');
            $table->dropColumn('inbox_global_link');
            $table->dropColumn('inbox_global_id_reference');
        });
    }
}
