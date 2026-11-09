<?php

namespace App\Jobs;

use App\Models\ComplianceCase;
use App\Services\Compliance\AmlRiskAssessmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAmlRiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public ComplianceCase $case) {}

    public function handle(AmlRiskAssessmentService $riskService): void
    {
        $riskService->assessCase($this->case);
    }
}
