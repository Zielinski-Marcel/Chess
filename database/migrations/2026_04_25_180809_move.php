<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('player_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('from_x');
            $table->unsignedTinyInteger('from_y');
            $table->unsignedTinyInteger('to_x');
            $table->unsignedTinyInteger('to_y');

            $table->string('piece', 1);

            $table->string('captured', 1)->nullable();

            $table->string('promotion', 1)->nullable();

            $table->text('fen')->nullable();

            $table->unsignedInteger('move_number');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
