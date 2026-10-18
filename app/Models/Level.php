<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Level extends Model
{
    protected $fillable = ['formation_id', 'name', 'position'];

    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
}
