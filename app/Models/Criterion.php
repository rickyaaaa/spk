<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $fillable = [
        'code',
        'name',
        'weight',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }
}
