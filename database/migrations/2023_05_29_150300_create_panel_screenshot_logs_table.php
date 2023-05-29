<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePanelScreenshotLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable("panel_screenshot_logs")) {
            Schema::dropIfExists('panel_screenshot_logs');
        }

        Schema::create('panel_screenshot_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_license');
            $table->string('target_license')->nullable(true);
            $table->integer('target_character')->nullable(true);
            $table->string('type');
            $table->string('url');
            $table->integer('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('panel_screenshot_logs');
    }
}
