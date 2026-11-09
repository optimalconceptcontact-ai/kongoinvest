<?php

namespace App\Services\Compliance\Providers;

use App\Contracts\Compliance\OcrProviderContract;
use App\Models\KycDocument;

class MockOcrProvider implements OcrProviderContract
{
    public function processDocument(KycDocument $document): array
    {
        $profile = $document->kycProfile;
        $isExpired = $document->isExpired();

        // Simulate high confidence extraction for valid documents
        $fieldScores = [
            'first_name' => 0.96,
            'last_name' => 0.94,
            'postnom' => 0.92,
            'date_of_birth' => 0.95,
            'document_number' => 0.98,
            'issuing_country' => 0.99,
        ];

        // Overall OCR reading confidence (remember: score > 0.85 DOES NOT mean identity validation!)
        $overallScore = round(array_sum($fieldScores) / count($fieldScores), 2);

        $extractedFields = [
            'first_name' => $profile?->first_name ?? 'Jean',
            'last_name' => $profile?->last_name ?? 'Ilunga',
            'postnom' => $profile?->postnom ?? 'Mwamba',
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d') ?? '1985-06-15',
            'issuing_country' => $document->issuing_country,
            'document_type' => $document->document_type->value,
        ];

        $inconsistencies = [];
        if ($isExpired) {
            $inconsistencies[] = 'Document expiré à la date de soumission.';
        }

        return [
            'provider' => 'mock_ocr_v1',
            'is_readable' => true,
            'extracted_fields' => $extractedFields,
            'field_confidence_scores' => $fieldScores,
            'overall_ocr_score' => $overallScore,
            'is_expired' => $isExpired,
            'tampering_detected' => false,
            'inconsistencies' => $inconsistencies,
            'summary' => 'Document lisible. Extraction OCR effectuée avec succès.',
        ];
    }
}
