<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomToAutocrmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('autocrms', function (Blueprint $table) {
            $table->text('custom_text_replace')->nullable()->after('autocrm_forward_email_content');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('autocrms', function (Blueprint $table) {
            $table->dropColumn('custom_text_replace');
        });
    }
}
