<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'name',
        'code',
        'building',
        'floor',
        'capacity',
        'type',
        'equipment',
        'status',
    ];

    protected function casts(): array
    {
        return ['equipment' => 'array'];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
