<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingCriteriaWeight extends Model
{
    protected $fillable = ['criterion', 'weight'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:3'];
    }
}
