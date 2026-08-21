<?php

namespace App\Data;

use App\Models\Sector;
use Spatie\LaravelData\Data;

class SectorData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $icon,
    ) {}

    public static function fromModel(Sector $sector): self
    {
        return new self($sector->id, $sector->name, $sector->slug, $sector->icon);
    }
}
