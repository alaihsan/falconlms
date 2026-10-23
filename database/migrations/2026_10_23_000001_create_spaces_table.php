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
     * A space is where content lives: a teacher's personal library, the
     * school library, or a class. Class details are stored on the same row.
     */
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('subject')->nullable();
            $table->string('join_code')->nullable()->unique();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        // Every teacher has exactly one personal library.
        DB::statement("CREATE UNIQUE INDEX spaces_personal_library_owner_unique ON spaces (owner_id) WHERE kind = 'personal_library'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
