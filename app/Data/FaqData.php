<?php

namespace App\Data;

use App\Models\Faq;
use Spatie\LaravelData\Data;

class FaqData extends Data
{
    public function __construct(
        public int $id,
        public string $category,
        public string $question,
        public string $answer,
    ) {}

    public static function fromModel(Faq $faq): self
    {
        return new self($faq->id, $faq->category, $faq->question, $faq->answer);
    }
}
