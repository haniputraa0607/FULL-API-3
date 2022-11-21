<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantGradingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('merchants', function (Blueprint $table) {
            $table->tinyInteger('reseller_status')->default(0)->after('merchant_count_transaction');
            $table->tinyInteger('auto_grading')->default(0)->after('reseller_status');
        });

        Schema::create('merchant_gradings', function (Blueprint $table) {
            $table->bigIncrements('id_merchant_grading');
            $table->unsignedBigInteger('id_merchant');
            $table->string('grading_name');
            $table->integer('min_qty');
            $table->integer('min_nominal');
            $table->timestamps();

			$table->foreign('id_merchant', 'fk_merchant_grading')->references('id_merchant')->on('merchants')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('reseller_status');
            $table->dropColumn('auto_grading');
        });

        Schema::dropIfExists('merchant_gradings');
    }
}
