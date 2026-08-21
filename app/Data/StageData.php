<?php

namespace App\Data;

use App\Models\Stage;
use Spatie\LaravelData\Data;

class StageData extends Data
{
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
    ) {}

    public static function fromModel(Stage $stage): self
    {
        return new self($stage->id, $stage->key, $stage->name);
    }
}
