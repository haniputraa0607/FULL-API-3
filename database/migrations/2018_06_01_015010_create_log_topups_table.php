<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTopupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_topups', function(Blueprint $table)
		{
			$table->integer('id_log_topup', true);
			$table->integer('id_user')->unsigned()->index('fk_log_topups_users');
			$table->integer('balance_before')->default(0);
			$table->integer('topup_value')->default(0);
			$table->integer('balance_after')->default(0);
			$table->integer('transaction_reference')->nullable();
			$table->timestamps();
		});
		
		Schema::table('log_topups', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_log_topups_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('log_topups', function(Blueprint $table)
		{
			$table->dropForeign('fk_log_topups_users');
		});
		Schema::dropIfExists('log_topups');
    }
}
