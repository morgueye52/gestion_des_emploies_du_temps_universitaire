<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentGroup extends Model
{
    protected $table = 'groups';

    protected $fillable = ['academic_class_id', 'name', 'code', 'capacity'];

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'group_id');
    }
}
