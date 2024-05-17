<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGardensTable extends Migration
{
    public function up()
    {
        Schema::create('gardens', function (Blueprint $table) {
    
                $table->id();
                $table->string('name');
                $table->string('location');
                $table->integer('area');
                $table->boolean('is_inside');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
        });

        Schema::create('garden_plant', function (Blueprint $table) {
                $table->id();
                $table->foreignId('garden_id')->constrained()->onDelete('cascade');
                $table->foreignId('plant_id')->constrained()->onDelete('cascade');
                $table->integer('spacing');
                $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('garden_plant');
        Schema::dropIfExists('gardens');
    }
}

