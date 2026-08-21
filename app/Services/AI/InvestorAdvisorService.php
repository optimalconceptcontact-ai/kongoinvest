<?php

namespace App\Services\Ai;

use App\Contracts\AiConversationProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Spec §54 — preset questions (risks, what to verify, red flags, due
 * diligence, funding coherence) plus free text. The AI never makes the
 * investment decision for the investor (spec §55/§148) — it answers
 * analytical questions, the investor decides.
 */
class InvestorAdvisorService
{
    public function __construct(
        private AiConversationProviderInterface $provider,
        private AiUsageService $usage,
    ) {}

    public function ask(User $investor, Project $project, string $question): AiConversation
    {
        $this->usage->assertWithinQuota($investor);

        // AiConversation::$fillable is deliberately empty — firstOrCreate()
        // would silently drop every attribute on the way to create(), so
        // this looks up first and forceCreate()s only when missing.
        $attributes = ['user_id' => $investor->id, 'project_id' => $project->id, 'type' => AiInteractionType::InvestorAdvisor->value];
        $conversation = AiConversation::where($attributes)->first() ?? AiConversation::forceCreate($attributes);

        return DB::transaction(function () use ($investor, $project, $question, $conversation) {
            $conversation->messages()->create(['role' => 'user', 'content' => $question]);

            $history = $conversation->messages()
                ->get()
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $result = $this->provider->respond($this->systemPrompt($project), array_slice($history, 0, -1), $question);

            if ($result === null) {
                $this->usage->record($investor, AiInteractionType::InvestorAdvisor, AiRequestStatus::Failed, $project);
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => "Je n'ai pas pu générer de réponse pour le moment. Réessayez dans quelques instants.",
                ]);

                return $conversation->fresh('messages');
            }

            $this->usage->record($investor, AiInteractionType::InvestorAdvisor, AiRequestStatus::Completed, $project, 'anthropic', $result->tokensUsed);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $result->text,
                'tokens_used' => $result->tokensUsed,
            ]);

            return $conversation->fresh('messages');
        });
    }

    private function systemPrompt(Project $project): string
    {
        return <<<PROMPT
            Tu es un conseiller en investissement qui aide un investisseur à
            évaluer un projet entrepreneurial basé en République
            Démocratique du Congo, intitulé « {$project->title} ».

            RÈGLE ABSOLUE (spec §54-55, §148) : tu ne prends JAMAIS la
            décision d'investissement à la place de l'investisseur. Tu
            n'affirmes jamais qu'un projet "va réussir" ou "est un bon
            investissement" de façon catégorique — tu fournis une analyse
            équilibrée (risques, signaux positifs, questions à poser, points
            de vigilance) pour éclairer sa propre décision.

            Distingue toujours les faits fournis sur le projet de tes
            propres suppositions ou évaluations. Si une information
            manque pour répondre correctement, dis-le explicitement plutôt
            que de deviner.
            PROMPT;
    }
}
