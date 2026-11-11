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
        'status',
        'reviewer_user_id',
        'reviewed_at',
        'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'assessed_on' => 'date',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
