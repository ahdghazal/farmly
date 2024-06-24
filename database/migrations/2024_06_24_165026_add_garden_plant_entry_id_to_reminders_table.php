<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGardenPlantEntryIdToRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedBigInteger('garden_plant_entry_id')->nullable()->after('garden_id');

            // Assuming GardenPlantEntry is the name of the related model
            $table->foreign('garden_plant_entry_id')
                  ->references('id')
                  ->on('garden_plant_entries')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropForeign(['garden_plant_entry_id']);
            $table->dropColumn('garden_plant_entry_id');
        });
    }
}
