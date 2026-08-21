<?php

namespace App\Models;

use App\Enums\DrcProvince;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepreneurProfile extends Model
{
    /** @use HasFactory<\Database\Factories\EntrepreneurProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'job_title', 'bio', 'experience',
        'province', 'city', 'years_experience', 'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'province' => DrcProvince::class,
            'onboarding_completed' => 'boolean',
            'years_experience' => 'integer',
        ];
    }

    // country_code is intentionally NOT mass-assignable — it is always
    // 'CD' at the DB default and enforced by a CHECK constraint
    // (spec §15). Never let a request set it.

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
