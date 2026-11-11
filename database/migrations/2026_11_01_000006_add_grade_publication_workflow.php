<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_grades', function (Blueprint $table) {
            $table->string('status', 16)->default('published');
            $table->foreignId('reviewer_user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('course_grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_user_id');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reviewed_at', 'review_comment']);
        });
    }
};
