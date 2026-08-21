<?php

namespace App\Services\Ai;

use App\Contracts\AiConversationProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Spec §53 — "Comment faire passer mon projet de 72 à 85 ?" The one
 * rule that matters most here is explicit in the spec itself: the AI
 * must never encourage fake traction, fake revenue, fake investors, or
 * fake documents, and must never suggest manipulating the score. That
 * instruction lives in the system prompt below, not as an
 * afterthought — it's the first rule stated, before anything about tone
 * or helpfulness.
 */
class CoachService
{
    public function __construct(
        private AiConversationProviderInterface $provider,
        private AiUsageService $usage,
    ) {}

    public function ask(User $entrepreneur, Project $project, string $question): AiConversation
    {
        if (! $project->isOwnedBy($entrepreneur)) {
            throw ValidationException::withMessages(['project' => 'Vous ne pouvez demander des conseils que pour votre propre projet.']);
        }

        $this->usage->assertWithinQuota($entrepreneur);

        // AiConversation::$fillable is deliberately empty — firstOrCreate()
        // would silently drop every attribute on the way to create(), so
        // this looks up first and forceCreate()s only when missing.
        $attributes = ['user_id' => $entrepreneur->id, 'project_id' => $project->id, 'type' => AiInteractionType::EntrepreneurCoach->value];
        $conversation = AiConversation::where($attributes)->first() ?? AiConversation::forceCreate($attributes);

        return DB::transaction(function () use ($entrepreneur, $project, $question, $conversation) {
            $conversation->messages()->create(['role' => 'user', 'content' => $question]);

            $history = $conversation->messages()
                ->where('role', '!=', 'system')
                ->get()
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $result = $this->provider->respond($this->systemPrompt($project), array_slice($history, 0, -1), $question);

            if ($result === null) {
                $this->usage->record($entrepreneur, AiInteractionType::EntrepreneurCoach, AiRequestStatus::Failed, $project);
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => "Je n'ai pas pu générer de réponse pour le moment. Réessayez dans quelques instants.",
                ]);

                return $conversation->fresh('messages');
            }

            $this->usage->record($entrepreneur, AiInteractionType::EntrepreneurCoach, AiRequestStatus::Completed, $project, 'anthropic', $result->tokensUsed);

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
            Tu es un coach en amélioration de projet entrepreneurial, pour
            un projet basé en République Démocratique du Congo intitulé
            « {$project->title} ».

            RÈGLE ABSOLUE (spec §53) : tu ne dois JAMAIS encourager, suggérer
            ou même mentionner comme option :
            - une fausse traction ou de faux clients
            - de faux revenus ou de fausses métriques financières
            - de faux investisseurs ou partenaires
            - des documents falsifiés
            - toute manipulation du score d'investissement lui-même

            Ton rôle est d'aider l'entrepreneur à RÉELLEMENT améliorer son
            projet : identifier les faiblesses réelles, prioriser les
            actions concrètes, signaler les informations manquantes,
            suggérer une documentation légitime, et expliquer l'impact
            potentiel de ces améliorations sur la perception du projet par
            un investisseur.

            Distingue toujours les faits du projet (fournis ci-dessous) de
            tes propres suppositions ou recommandations.
            PROMPT;
    }
}
