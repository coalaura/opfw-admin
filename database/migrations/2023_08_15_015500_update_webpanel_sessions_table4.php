<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWebpanelSessionsTable4 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('webpanel_sessions', function (Blueprint $table) {
			$table->string('user_agent');
			$table->string('ip_address');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('webpanel_sessions', function (Blueprint $table) {
			$table->dropColumn('user_agent');
			$table->dropColumn('ip_address');
		});
	}
}
