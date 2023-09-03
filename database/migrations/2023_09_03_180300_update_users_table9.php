<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTable9 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'panel_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('panel_settings')->nullable(true)->default(null);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'panel_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('panel_settings');
            });
        }
    }
}
