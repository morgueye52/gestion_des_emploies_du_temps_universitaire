<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->foreignId('academic_class_id')
                ->nullable()
                ->after('group_id')
                ->constrained('academic_classes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_class_id');
        });
    }
};
