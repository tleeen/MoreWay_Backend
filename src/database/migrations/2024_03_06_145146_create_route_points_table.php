<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('route_points', function (Blueprint $table) {
            $table->id();
            $table->integer('index');
            $table->unsignedBigInteger('place_id');
            $table->unsignedBigInteger('route_id');
            $table->timestamps();

            $table->unique(['place_id', 'route_id']);

            $table->foreign('place_id')->references('id')->on('places')->cascadeOnUpdate();
            $table->foreign('route_id')->references('id')->on('routes')->cascadeOnUpdate()->cascadeOnDelete();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_points');
    }
};
