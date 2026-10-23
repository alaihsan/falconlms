<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Folders only exist in libraries. `path` lists the ancestor ids
     * ("/3/8/") so breadcrumbs and subtrees need a single query.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('path')->default('/')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
