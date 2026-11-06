<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $contract_document_id
 * @property int $version
 * @property string|null $title
 * @property array<string, mixed> $content
 * @property string|null $rendered_body
 * @property string $hash
 * @property int|null $created_by_user_id
 * @property array<string, mixed>|null $generation_meta
 */
class ContractDocumentVersion extends Model
{
    protected $fillable = []; // immutable snapshot — a change creates a new version row, metadata never rewritten

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'generation_meta' => 'array',
            'version' => 'integer',
        ];
    }

    /** @return BelongsTo<ContractDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<ContractSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class, 'contract_document_version_id');
    }
}
