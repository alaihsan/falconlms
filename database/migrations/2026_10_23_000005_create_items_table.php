<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Items are the content of a space: materials, assignments, files and
     * links. In a library they sit in a folder, in a class under a topic.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedSmallInteger('max_points')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('source_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'folder_id']);
            $table->index(['space_id', 'topic_id', 'position']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE items ADD CONSTRAINT items_folder_or_topic CHECK (folder_id IS NULL OR topic_id IS NULL)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
