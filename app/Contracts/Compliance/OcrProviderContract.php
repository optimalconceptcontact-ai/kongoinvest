<?php

namespace App\Contracts\Compliance;

use App\Models\KycDocument;

interface OcrProviderContract
{
    /**
     * Perform OCR extraction and authenticity analysis on a document.
     *
     * @return array{
     *     provider: string,
     *     is_readable: bool,
     *     extracted_fields: array<string, mixed>,
     *     field_confidence_scores: array<string, float>,
     *     overall_ocr_score: float,
     *     is_expired: bool,
     *     tampering_detected: bool,
     *     inconsistencies: array<string>,
     *     summary: string
     * }
     */
    public function processDocument(KycDocument $document): array;
}
