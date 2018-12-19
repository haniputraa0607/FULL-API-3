<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnCustomFieldNewsFormStructuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('news_form_structures', function(Blueprint $table)
		{
			$table->enum('form_input_autofill', ['Name','Phone','Email', 'Gender', 'City', 'Birthday'])->default(null)->nullable()->after('form_input_label');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('news_form_structures', function(Blueprint $table) {
            $table->dropColumn('form_input_autofill');
        });
    }
}
