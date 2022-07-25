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
            $table->string('inboxes_clickto')->after('inboxes_content');
			$table->string('inboxes_link')->after('inboxes_clickto');
			$table->string('inboxes_id_reference')->after('inboxes_link');
			$table->smallinteger('inboxes_promotion_status')->after('inboxes_id_reference');
			$table->boolean('read')->after('inboxes_promotion_status');
			$table->integer('id_brand')->after('read');
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
            $table->dropColumn('inboxes_clickto');
            $table->dropColumn('inboxes_link');
            $table->dropColumn('inboxes_id_reference');
            $table->dropColumn('inboxes_promotion_status');
            $table->dropColumn('read');
            $table->dropColumn('id_brand');
        });
    }
}
