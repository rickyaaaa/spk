<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'name',
        'class_name',
        'status',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(AhpResult::class);
    }
}
