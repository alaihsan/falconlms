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
        Schema::table('learning_items', function (Blueprint $table): void {
            $table->boolean('is_graded')->default(false)->after('max_score')->index();
        });

        DB::table('learning_items')
            ->whereIn('type', ['assignment', 'quiz'])
            ->update(['is_graded' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learning_items', function (Blueprint $table): void {
            $table->dropIndex(['is_graded']);
            $table->dropColumn('is_graded');
        });
    }
};
