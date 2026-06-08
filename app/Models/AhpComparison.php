<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AhpComparison extends Model
{
    protected $fillable = [
        'criterion_a_id',
        'criterion_b_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    public function criterionA(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_a_id')->withTrashed();
    }

    public function criterionB(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_b_id')->withTrashed();
    }
}
