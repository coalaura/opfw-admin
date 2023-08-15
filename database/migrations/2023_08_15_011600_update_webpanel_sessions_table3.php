<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWebpanelSessionsTable3 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('webpanel_sessions', function (Blueprint $table) {
			$table->string('last_viewed');
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
			$table->dropColumn('last_viewed');
		});
	}
}
