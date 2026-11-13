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
        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('room')->nullable();
            $table->string('code', 12)->unique();
            $table->text('description')->nullable();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['school_class_id', 'siswa_id']);
        });

        Schema::create('learning_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->index(); // announcement, material, assignment, quiz, topic
            $table->string('title');
            $table->string('topic')->nullable();
            $table->text('content')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_url')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_item_id')->constrained('learning_items')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['learning_item_id', 'siswa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('learning_items');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('school_classes');
    }
};
