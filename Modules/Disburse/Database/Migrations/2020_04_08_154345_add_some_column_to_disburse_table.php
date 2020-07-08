<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToDisburseTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('recipient_name');
            $table->string('beneficiary_alias', 191)->nullable()->after('recipient_name');
            $table->string('beneficiary_email', 191)->nullable()->after('recipient_name');
            $table->string('reference_no', 200)->nullable()->after('response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disburse', function (Blueprint $table) {
            $table->drop('notes');
            $table->drop('beneficiary_alias');
            $table->drop('beneficiary_email');
            $table->drop('reference_no');
        });
    }
}
