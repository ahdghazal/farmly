<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGardenPlantEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('garden_plant_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garden_id');
            $table->unsignedBigInteger('plant_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('garden_id')->references('id')->on('gardens')->onDelete('cascade');
            $table->foreign('plant_id')->references('id')->on('plants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('garden_plant_entries');
    }
}
