<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUserBansTable3 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('user_bans', 'scheduled_unban')) {
			Schema::table('user_bans', function (Blueprint $table) {
				$table->integer('scheduled_unban')->nullable();
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
		if (!Schema::hasColumn('user_bans', 'scheduled_unban')) {
			Schema::table('user_bans', function (Blueprint $table) {
				$table->dropColumn('scheduled_unban');
			});
		}
	}
}
