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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->text('body')->nullable();
            $table->timestamp('turned_in_at')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['item_id', 'student_id']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
