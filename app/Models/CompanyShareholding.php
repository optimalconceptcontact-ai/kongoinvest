<?php

namespace App\Models;

use App\Enums\DataVerificationStatus;
use App\Enums\ShareholderType;
use Database\Factories\CompanyShareholdingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyShareholding extends Model
{
    /** @use HasFactory<CompanyShareholdingFactory> */
    use HasFactory;

    protected $fillable = []; // every field is set exclusively via trusted Actions / seeders — app/Actions/**

    protected function casts(): array
    {
        return [
            'holder_type' => ShareholderType::class,
            'verification' => DataVerificationStatus::class,
            'share_count' => 'integer',
            'percentage_bps' => 'integer',
            'is_founder' => 'boolean',
            'is_beneficial_owner' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function percentageLabel(): ?string
    {
        if ($this->percentage_bps === null) {
            return null;
        }

        return rtrim(rtrim(number_format($this->percentage_bps / 100, 2, '.', ''), '0'), '.').' %';
    }
}
