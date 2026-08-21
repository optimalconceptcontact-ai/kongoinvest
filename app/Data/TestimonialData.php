<?php

namespace App\Data;

use App\Models\Testimonial;
use Spatie\LaravelData\Data;

class TestimonialData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $role,
        public ?string $company,
        public ?string $countryCode,
        public string $content,
        public ?string $photoPath,
    ) {}

    public static function fromModel(Testimonial $testimonial): self
    {
        return new self(
            $testimonial->id,
            $testimonial->name,
            $testimonial->role,
            $testimonial->company,
            $testimonial->country_code,
            $testimonial->content,
            $testimonial->photo_path,
        );
    }
}
