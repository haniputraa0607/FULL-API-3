<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnReadToUserInboxesTable extends Migration
{
    public function __construct() 
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    
    public function up()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->char('read', '1')->after('inboxes_send_at')->default('0');
        });
    }

    public function down()
    {
        Schema::table('user_inboxes', function (Blueprint $table) {
            $table->dropColumn('read');
        });
    }
}
