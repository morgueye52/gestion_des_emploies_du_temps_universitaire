<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGrade extends Model
{
    protected $fillable = [
        'course_id',
        'student_id',
        'teacher_id',
        'assessment_name',
        'assessment_type',
        'score',
        'assessed_on',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'assessed_on' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
