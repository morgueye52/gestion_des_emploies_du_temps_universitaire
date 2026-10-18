<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_name', 120);
            $table->string('assessment_type', 32);
            $table->decimal('score', 5, 2);
            $table->date('assessed_on');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(
                ['course_id', 'student_id', 'assessment_name', 'assessed_on'],
                'course_grade_assessment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_grades');
    }
};
