<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalReference extends Model
{
    // Base juridique structurée et versionnée : endpoint
    // d'administration/seed, champs librement assignables ici.
    protected $fillable = [
        'jurisdiction',
        'act_title',
        'article',
        'version',
        'effective_from',
        'source_url',
        'summary',
        'keywords',
        'verified_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'verified_at' => 'datetime',
        'keywords' => 'array',
    ];

    /**
     * Citation — jamais d'article inventé : une référence sans
     * article vérifié sert de context, pas de fondement.
     */
    public function citation(): string
    {
        $version = $this->version ? ' — '.$this->version : '';

        return $this->act_title.($this->article ? ', '.$this->article : '').$version;
    }
}
