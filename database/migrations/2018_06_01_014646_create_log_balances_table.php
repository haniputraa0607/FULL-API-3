<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_balances', function(Blueprint $table)
		{
			$table->integer('id_log_balance', true);
			$table->integer('id_user')->unsigned()->index('fk_log_balances_users');
			$table->integer('balance')->default(0);
			$table->integer('id_reference')->nullable();
			$table->string('source', 191)->nullable();
			$table->integer('grand_total')->default(0);
			$table->integer('ccashback_conversion')->default(0);
			$table->string('membership_level')->nullable()->default(null);
			$table->integer('membership_cashback_percentage')->default(0);
			$table->timestamps();
		});
		
		Schema::table('log_balances', function(Blueprint $table)
		{
			$table->foreign('id_user', 'fk_log_balances_users')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('log_balances', function(Blueprint $table)
		{
			$table->dropForeign('fk_log_balances_users');
		});
		Schema::dropIfExists('log_balances');
    }
}
