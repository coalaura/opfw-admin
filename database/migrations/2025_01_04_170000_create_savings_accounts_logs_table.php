<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSavingsAccountsLogsTable extends Migration
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

		$tableExists = Schema::hasTable("savings_accounts_logs");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("savings_accounts_logs", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("id", $columns) && $table->integer("id")->autoIncrement(); // primary key
			!in_array("account_id", $columns) && $table->integer("account_id");
			!in_array("character_id", $columns) && $table->integer("character_id");
			!in_array("action", $columns) && $table->string("action", 50)->nullable();
			!in_array("amount", $columns) && $table->integer("amount")->nullable();
			!in_array("reason", $columns) && $table->string("reason", 255)->nullable();
			!in_array("timestamp", $columns) && $table->integer("timestamp")->nullable();

			!in_array("id", $indexes) && $table->index("id");
			!in_array("account_id", $indexes) && $table->index("account_id");
			!in_array("character_id", $indexes) && $table->index("character_id");
			!in_array("timestamp", $indexes) && $table->index("timestamp");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("savings_accounts_logs");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `savings_accounts_logs`");

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
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `savings_accounts_logs` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}