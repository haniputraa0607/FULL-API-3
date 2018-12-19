<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReplyEnquiriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enquiries', function(Blueprint $table)
		{
			$table->string('enquiry_device_token')->nullable()->default(null)->after('enquiry_status');
			$table->string('reply_email_subject')->nullable()->default(null)->after('enquiry_device_token');
			$table->text('reply_email_content', 16777215)->nullable()->after('reply_email_subject');
			$table->text('reply_sms_content', 65535)->nullable()->after('reply_email_content');
			$table->text('reply_push_subject', 65535)->nullable()->after('reply_sms_content');
			$table->text('reply_push_content', 65535)->nullable()->after('reply_push_subject');
			$table->string('reply_push_image')->nullable()->default(null)->after('reply_push_content');
			$table->string('reply_push_clickto', 100)->nullable()->default(null)->after('reply_push_image');
			$table->string('reply_push_link')->nullable()->default(null)->after('reply_push_clickto');
			$table->string('reply_push_id_reference')->nullable()->default(null)->after('reply_push_link');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_points', function(Blueprint $table) {
            $table->dropColumn('enquiry_device_token');
            $table->dropColumn('reply_email_subject');
            $table->dropColumn('reply_email_content');
            $table->dropColumn('reply_sms_content');
            $table->dropColumn('reply_push_subject');
            $table->dropColumn('reply_push_content');
            $table->dropColumn('reply_push_image');
            $table->dropColumn('reply_push_clickto');
            $table->dropColumn('reply_push_link');
            $table->dropColumn('reply_push_id_reference');
        });
    }
}
