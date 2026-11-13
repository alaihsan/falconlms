<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
        });

        Schema::create('learning_item_class', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_item_id')->constrained('learning_items')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learning_item_id', 'school_class_id']);
        });

        Schema::create('learning_item_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_item_id')->constrained('learning_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learning_item_id', 'user_id']);
        });

        DB::table('learning_items')
            ->whereNotNull('school_class_id')
            ->orderBy('id')
            ->each(function (object $item): void {
                DB::table('learning_item_class')->insert([
                    'learning_item_id' => $item->id,
                    'school_class_id' => $item->school_class_id,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ]);
            });

        Schema::table('learning_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropColumn('attachment_url');
        });

        Schema::table('learning_items', function (Blueprint $table): void {
            $table->foreignId('folder_id')->nullable()->after('user_id')->constrained('folders')->nullOnDelete();
            $table->string('attachment_path')->nullable()->after('attachment_name');
            $table->string('attachment_mime')->nullable()->after('attachment_path');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn(['attachment_path', 'attachment_mime', 'attachment_size']);
        });

        Schema::table('learning_items', function (Blueprint $table): void {
            $table->foreignId('school_class_id')->nullable()->after('id')->constrained('school_classes')->nullOnDelete();
            $table->string('attachment_url')->nullable()->after('attachment_name');
        });

        DB::table('learning_item_class')
            ->orderBy('id')
            ->each(function (object $link): void {
                DB::table('learning_items')
                    ->where('id', $link->learning_item_id)
                    ->whereNull('school_class_id')
                    ->update(['school_class_id' => $link->school_class_id]);
            });

        Schema::dropIfExists('learning_item_shares');
        Schema::dropIfExists('learning_item_class');
        Schema::dropIfExists('folders');
    }
};
