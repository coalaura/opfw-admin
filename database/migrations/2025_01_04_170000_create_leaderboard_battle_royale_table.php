<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaderboardBattleRoyaleTable extends Migration
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

		$tableExists = Schema::hasTable("leaderboard_battle_royale");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("leaderboard_battle_royale", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("id", $columns) && $table->integer("id")->autoIncrement(); // primary key
			!in_array("character_id", $columns) && $table->integer("character_id")->nullable();
			!in_array("kills", $columns) && $table->integer("kills")->nullable()->default("0");
			!in_array("deaths", $columns) && $table->integer("deaths")->nullable()->default("0");
			!in_array("hits", $columns) && $table->integer("hits")->nullable()->default("0");
			!in_array("hits_headshot", $columns) && $table->integer("hits_headshot")->nullable()->default("0");
			!in_array("damage_dealt", $columns) && $table->integer("damage_dealt")->nullable()->default("0");
			!in_array("damage_taken", $columns) && $table->integer("damage_taken")->nullable()->default("0");
			!in_array("wins", $columns) && $table->integer("wins")->nullable()->default("0");
			!in_array("matches", $columns) && $table->integer("matches")->nullable()->default("0");

			!in_array("character_id", $indexes) && $table->index("character_id");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("leaderboard_battle_royale");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `leaderboard_battle_royale`");

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
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `leaderboard_battle_royale` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}