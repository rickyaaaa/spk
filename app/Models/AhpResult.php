<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AhpResult extends Model
{
    protected $fillable = [
        'student_id',
        'final_score',
        'rank_position',
        'evaluation_period',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'rank_position' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
