<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'garden_plant_entry_id')) {
                $table->unsignedBigInteger('garden_plant_entry_id')->nullable();
            }
            if (!Schema::hasColumn('reminders', 'plant_entry_id')) {
                $table->unsignedBigInteger('plant_entry_id')->default(0);
            }
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
        
        });
    }
}
