<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionTypeToTransaction extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement('ALTER TABLE transactions CHANGE trasaction_type trasaction_type ENUM("Pickup Order", "Delivery", "Offline", "Advance Order", "Consultation") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement('ALTER TABLE transactions CHANGE trasaction_type trasaction_type ENUM("Pickup Order", "Delivery", "Offline", "Advance Order") CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
        });
    }
}
