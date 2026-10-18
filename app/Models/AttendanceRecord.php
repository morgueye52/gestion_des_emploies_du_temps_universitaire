<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'session_id',
        'student_id',
        'teacher_id',
        'attendance_date',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Seance::class, 'session_id');
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
