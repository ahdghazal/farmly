<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('soil_type');
            $table->string('category');
            $table->string('fertilization');
            $table->string('pruning');
            $table->boolean('support');
            $table->string('spacing');
            $table->string('season');
            $table->string('water_need');
            $table->string('light_needed');
            $table->string('temperature');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plants');
    }
};
