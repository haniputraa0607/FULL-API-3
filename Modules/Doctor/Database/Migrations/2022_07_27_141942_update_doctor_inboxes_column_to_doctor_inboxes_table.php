<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDoctorInboxesColumnToDoctorInboxesTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('doctor_inboxes', function (Blueprint $table) {
            $table->dropColumn('inboxes_promotion_status');
			$table->string('inboxes_category')->after('inboxes_id_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doctor_inboxes', function (Blueprint $table) {
			$table->boolean('read')->after('inboxes_promotion_status');
            $table->dropColumn('inboxes_category');
        });
    }
}
