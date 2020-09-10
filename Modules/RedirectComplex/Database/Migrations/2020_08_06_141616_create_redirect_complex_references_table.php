<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedirectComplexReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redirect_complex_references', function (Blueprint $table) {
            $table->increments('id_redirect_complex_reference');
            $table->string('type');
            $table->string('outlet_type');
			$table->string('promo_type')->nullable();
			$table->string('promo_reference')->nullable();

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
        Schema::dropIfExists('redirect_complex_references');
    }
}
