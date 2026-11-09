<?php

namespace App\Jobs;

use App\Contracts\Compliance\OcrProviderContract;
use App\Models\KycDocument;
use App\Models\KycDocumentCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDocumentOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public KycDocument $document) {}

    public function handle(OcrProviderContract $ocrProvider): void
    {
        // Idempotency check: if document already has a check recorded, avoid duplicate
        if ($this->document->checks()->exists()) {
            return;
        }

        $ocrResult = $ocrProvider->processDocument($this->document);

        KycDocumentCheck::create([
            'kyc_document_id' => $this->document->id,
            'provider' => $ocrResult['provider'],
            'is_readable' => $ocrResult['is_readable'],
            'is_expired' => $this->document->isExpired() || $ocrResult['is_expired'],
            'is_duplicate' => false,
            'tampering_detected' => $ocrResult['tampering_detected'],
            'data_matches_form' => true,
            'extracted_fields' => $ocrResult['extracted_fields'],
            'field_confidence_scores' => $ocrResult['field_confidence_scores'],
            'overall_ocr_score' => $ocrResult['overall_ocr_score'],
            'inconsistencies_detected' => $ocrResult['inconsistencies'],
            'check_summary' => $ocrResult['summary'],
        ]);

        $this->document->status = 'processed';
        $this->document->save();
    }
}
