<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideArticle extends Model
{
    use HasFactory;

    /**
     * Guide sections, keyed by the public guide tab they appear under.
     * Shared with the admin form validation so both stay in sync.
     *
     * @var array<string, string>
     */
    public const SECTIONS = [
        'financing-types' => 'Types de financement',
        'financial-instruments' => 'Instruments financiers',
        'stages' => "Stades de l'entreprise",
        'financing-sources' => 'Sources de financement',
        'financing-channels' => 'Canaux de financement',
        'ohada-compliance' => 'Conformité OHADA / RDC',
        'glossary' => 'Glossaire',
    ];

    protected $fillable = [
        'section',
        'title',
        'description',
        'content',
        'order',
        'is_published',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('order');
    }
}
