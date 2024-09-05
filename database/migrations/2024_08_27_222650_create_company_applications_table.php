<?php
// Auto generated by the build:migrations command

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyApplicationsTable extends Migration
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

		$tableExists = Schema::hasTable("company_applications");

		$indexes = $tableExists ? $this->getIndexedColumns() : [];
		$columns = $tableExists ? $this->getColumns() : [];

		$func = $tableExists ? "table" : "create";

		Schema::$func("company_applications", function (Blueprint $table) use ($columns, $indexes) {
			!in_array("id", $columns) && $table->integer("id")->autoIncrement();
			!in_array("app_applier_cid", $columns) && $table->integer("app_applier_cid")->nullable();
			!in_array("app_name", $columns) && $table->longText("app_name")->nullable();
			!in_array("app_description", $columns) && $table->longText("app_description")->nullable();
			!in_array("contact_person", $columns) && $table->longText("contact_person")->nullable();
			!in_array("estimated_employees", $columns) && $table->longText("estimated_employees")->nullable();
			!in_array("app_logo", $columns) && $table->longText("app_logo")->nullable();
			!in_array("todays_date", $columns) && $table->longText("todays_date")->nullable();
			!in_array("signature", $columns) && $table->longText("signature")->nullable();

			!in_array("id", $indexes) && $table->index("id");
			!in_array("app_applier_cid", $indexes) && $table->index("app_applier_cid");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists("company_applications");
	}

	/**
	 * Get all columns.
	 *
	 * @return array
	 */
	private function getColumns(): array
	{
		$columns = Schema::getConnection()->select("SHOW COLUMNS FROM `company_applications`");

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
		$indexes = Schema::getConnection()->select("SHOW INDEXES FROM `company_applications` WHERE Key_name != 'PRIMARY'");

		return array_map(function ($index) {
			return $index->Column_name;
		}, $indexes);
	}
}