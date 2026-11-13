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
        Schema::table('learning_items', function (Blueprint $table): void {
            $table->string('url', 2048)->nullable()->after('content');
        });

        Schema::create('poll_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_item_id')->constrained('learning_items')->cascadeOnDelete();
            $table->string('label', 200);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_item_id')->constrained('learning_items')->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained('poll_options')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learning_item_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');

        Schema::table('learning_items', function (Blueprint $table): void {
            $table->dropColumn('url');
        });
    }
};
