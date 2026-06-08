<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScore extends Model
{
    protected $fillable = [
        'student_id',
        'criterion_id',
        'evaluation_period',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
