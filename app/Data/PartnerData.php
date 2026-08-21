<?php

namespace App\Data;

use App\Models\Faq;
use App\Models\Partner;
use App\Models\Testimonial;
use Spatie\LaravelData\Data;

class PartnerData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $category,
        public ?string $logoPath,
        public ?string $website,
    ) {}

    public static function fromModel(Partner $partner): self
    {
        return new self($partner->id, $partner->name, $partner->category, $partner->logo_path, $partner->website);
    }
}
