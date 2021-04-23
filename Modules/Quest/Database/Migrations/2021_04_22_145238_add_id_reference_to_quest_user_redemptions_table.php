<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdReferenceToQuestUserRedemptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quest_user_redemptions', function (Blueprint $table) {
            $table->enum('benefit_type', ['point', 'voucher'])->after('redemption_status')->nullable();
            $table->unsignedInteger('id_reference')->after('benefit_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quest_user_redemptions', function (Blueprint $table) {
            $table->dropColumn('id_reference');
            $table->dropColumn('benefit_type');
        });
    }
}
