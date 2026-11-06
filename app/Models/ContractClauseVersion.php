<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractClauseVersion extends Model
{
    protected $fillable = []; // snapshot immutable — created only by library versioning code

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'change_summary' => 'array',
        ];
    }

    public function clause(): BelongsTo
    {
        return $this->belongsTo(ContractClauseLibrary::class, 'contract_clause_library_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
