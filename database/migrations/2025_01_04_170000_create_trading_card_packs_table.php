<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradingCardPacksTable extends Migration
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

		$tableExists = Schema::hasTable("trading_card_packs");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("trading_card_packs", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("pack_id", $columns) && $table->integer("pack_id")->autoIncrement(); // primary key
			!in_array("parent_pack_id", $columns) && $table->integer("parent_pack_id")->nullable();
			!in_array("title", $columns) && $table->string("title", 120)->nullable();
			!in_array("pack_icon_url", $columns) && $table->string("pack_icon_url", 255)->nullable();
			!in_array("card_icon_url", $columns) && $table->string("card_icon_url", 255)->nullable();
			!in_array("price", $columns) && $table->integer("price")->nullable()->default("1250");
			!in_array("drop_amount", $columns) && $table->integer("drop_amount")->nullable()->default("3");
			!in_array("additional_drops", $columns) && $table->longText("additional_drops")->nullable();
			!in_array("disabled", $columns) && $table->tinyInteger("disabled")->nullable()->default("0");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("trading_card_packs");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `trading_card_packs`");

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
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `trading_card_packs` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}