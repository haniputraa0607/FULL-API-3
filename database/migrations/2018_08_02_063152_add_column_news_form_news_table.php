<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnNewsFormNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news', function(Blueprint $table)
		{
			$table->string('news_button_form_text', 50)->default(null)->nullable()->after('news_product_text');
			$table->dateTime('news_button_form_expired')->default(null)->nullable()->after('news_button_form_text');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news', function(Blueprint $table) {
            $table->dropColumn('news_button_form_text');
            $table->dropColumn('news_button_form_expired');
        });
    }
}
