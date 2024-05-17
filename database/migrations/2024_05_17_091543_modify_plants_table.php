<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyPlantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {            
        
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn('spacing');
        });
        
        Schema::table('plants', function (Blueprint $table) {
            $table->double('spacing')->nullable();
            $table->dropColumn('temperature');
            $table->integer('min_temperature')->nullable();
            $table->integer('max_temperature')->nullable();
            $table->integer('favorites_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plants', function (Blueprint $table) {
            // Revert the changes made in the up() method
            $table->string('spacing')->nullable(false)->change();
            $table->string('temperature');
            $table->dropColumn(['min_temperature', 'max_temperature', 'favorites_count']);
        });
    }
}
