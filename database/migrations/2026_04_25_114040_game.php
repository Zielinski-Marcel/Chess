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
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            $table->foreignId('white_player_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('black_player_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['playing', 'finished']);
            $table->enum('turn', ['w', 'b'])->default('w');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('fen')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
