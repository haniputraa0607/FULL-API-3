<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateLogTopupsFieldToLogTopupsTable extends Migration
{
    public function up()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->string('source', 100)->nullable()->after('transaction_reference');
        });
    }

    public function down()
    {
        Schema::table('log_topups', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
