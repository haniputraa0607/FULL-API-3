<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNoLimitValueToNewPurchaseAfterColumnOnSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN new_purchase_after ENUM('No Limit', 'Empty','Expired','Empty Expired')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN new_purchase_after ENUM('Empty','Expired','Empty Expired')");
    }
}
