<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTwitterAccountsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if (!Schema::hasColumn('panel_ip_infos', 'is_verified')) {
			Schema::table('panel_ip_infos', function (Blueprint $table) {
				$table->string('city');
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
		if (Schema::hasColumn('panel_ip_infos', 'is_verified')) {
			Schema::table('panel_ip_infos', function (Blueprint $table) {
				$table->dropColumn('city');
			});
		}
	}
}
