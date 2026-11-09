<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Services\Financing\FinancingRequestService;
use Illuminate\Support\Facades\DB;

class UpdateProject
{
    public function handle(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->fill(collect($data)->except(['sectors', 'stages', 'financing_request', 'financing_components', 'financial_instruments'])->toArray());
            $project->save();

            $project->sectors()->sync($data['sectors']);
            $project->stages()->sync($data['stages']);

            // Sync financing request
            $financingData = $data['financing_request'] ?? [];
            if (! empty($data['financing_components'])) {
                $financingData['components'] = $data['financing_components'];
            } elseif (! empty($data['financial_instruments'])) {
                $financingData['components'] = array_map(fn ($id) => ['financial_instrument_id' => $id], $data['financial_instruments']);
            }

            if (! empty($financingData)) {
                app(FinancingRequestService::class)->syncForProject($project, $financingData);
            }

            return $project->fresh(['sectors', 'stages', 'latestFinancingRequest.components.instrument']);
        });
    }
}
