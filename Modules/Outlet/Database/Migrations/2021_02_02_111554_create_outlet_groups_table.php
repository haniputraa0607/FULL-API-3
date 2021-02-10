<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_groups', function (Blueprint $table) {
            $table->integerIncrements('id_outlet_group');
            $table->string('outlet_group_name', 200);
            $table->enum('outlet_group_type', ['Conditions', 'Outlets']);
            $table->enum('outlet_group_filter_rule', ['and', 'or'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_groups');
    }
}
