<?php

namespace App\Jobs;

use App\Models\ComplianceCase;
use App\Services\Compliance\AmlRiskAssessmentService;
use App\Services\Compliance\AmlScreeningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAmlScreeningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public ComplianceCase $case,
        public string $subjectName,
        public string $country = 'CD',
        public string $subjectType = 'individual',
        public ?string $sector = null
    ) {}

    public function handle(
        AmlScreeningService $screeningService,
        AmlRiskAssessmentService $riskService
    ): void {
        $screeningService->performScreening(
            $this->case,
            $this->subjectName,
            $this->country,
            $this->subjectType,
            $this->sector
        );

        // Automatically re-evaluate risk after screening
        $riskService->assessCase($this->case);
    }
}
