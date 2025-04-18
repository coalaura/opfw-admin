<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBansTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// Make enums work pre laravel 10
		Schema::getConnection()->getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping("enum", "string");

		$tableExists = Schema::hasTable("user_bans");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("user_bans", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("id", $columns) && $table->integer("id")->autoIncrement(); // primary key
			!in_array("ban_hash", $columns) && $table->string("ban_hash", 50)->nullable();
			!in_array("identifier", $columns) && $table->string("identifier", 50)->nullable();
			!in_array("smurf_account", $columns) && $table->string("smurf_account", 50)->nullable();
			!in_array("creator_name", $columns) && $table->string("creator_name", 255)->nullable();
			!in_array("reason", $columns) && $table->string("reason", 1024)->nullable();
			!in_array("timestamp", $columns) && $table->integer("timestamp")->nullable();
			!in_array("expire", $columns) && $table->integer("expire")->nullable();
			!in_array("creator_identifier", $columns) && $table->string("creator_identifier", 50)->default("");
			!in_array("smurf_reason", $columns) && $table->string("smurf_reason", 50)->nullable();
			!in_array("locked", $columns) && $table->tinyInteger("locked")->default("0");
			!in_array("scheduled_unban", $columns) && $table->integer("scheduled_unban")->nullable();
			!in_array("creation_reason", $columns) && $table->string("creation_reason", 50)->nullable();

			!in_array("ban_hash", $indexes) && $table->index("ban_hash");
			!in_array("identifier", $indexes) && $table->index("identifier");
			!in_array("smurf_account", $indexes) && $table->index("smurf_account");
			!in_array("timestamp", $indexes) && $table->index("timestamp");
			!in_array("expire", $indexes) && $table->index("expire");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("user_bans");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `user_bans`");

		return array_map(function ($column) {
			return $column->Field;
		}, $columns);
	}

	/**
	 * Get all indexed columns.
	 *
	 * @return array
	 */
	private function getIndexedColumns(): array
	{
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `user_bans` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}