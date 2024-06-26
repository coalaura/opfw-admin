<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDpkeybindsTable extends Migration
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

		$tableExists = Schema::hasTable("dpkeybinds");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("dpkeybinds", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("id", $columns) && $table->string("id", 50)->primary();
			!in_array("keybind1", $columns) && $table->string("keybind1", 50)->nullable()->default("num4");
			!in_array("emote1", $columns) && $table->string("emote1", 255)->nullable()->default("");
			!in_array("keybind2", $columns) && $table->string("keybind2", 50)->nullable()->default("num5");
			!in_array("emote2", $columns) && $table->string("emote2", 255)->nullable()->default("");
			!in_array("keybind3", $columns) && $table->string("keybind3", 50)->nullable()->default("num6");
			!in_array("emote3", $columns) && $table->string("emote3", 255)->nullable()->default("");
			!in_array("keybind4", $columns) && $table->string("keybind4", 50)->nullable()->default("num7");
			!in_array("emote4", $columns) && $table->string("emote4", 255)->nullable()->default("");
			!in_array("keybind5", $columns) && $table->string("keybind5", 50)->nullable()->default("num8");
			!in_array("emote5", $columns) && $table->string("emote5", 255)->nullable()->default("");
			!in_array("keybind6", $columns) && $table->string("keybind6", 50)->nullable()->default("num9");
			!in_array("emote6", $columns) && $table->string("emote6", 255)->nullable()->default("");

			!in_array("id", $indexes) && $table->index("id");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("dpkeybinds");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `dpkeybinds`");

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
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `dpkeybinds` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}