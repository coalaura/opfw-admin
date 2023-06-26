<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTable6 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'average_ping')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('average_ping')->nullable(true)->default(null);
            });
        }

        if (!Schema::hasColumn('users', 'average_fps')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('average_fps')->nullable(true)->default(null);
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('average_ping');
            $table->dropColumn('average_fps');
        });
    }
}
