<?php

namespace App\Services\Ai;

use App\Contracts\AiConversationProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Models\AiConversation;
use App\Models\InvestorProfile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Spec §54 — a conversational investment coach. Beyond analytical Q&A
 * (risks, red flags, due-diligence, funding coherence) it also helps the
 * investor orient their own decision — ticket sizing, whether to back
 * the project or take equity in the company, governance/management
 * involvement — always read against the investor's own profile (thesis,
 * ticket range, horizon, risk appetite, sectors, stages).
 *
 * It never makes the call for the investor (spec §55/§148): it frames
 * the trade-offs, the investor decides.
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

            $result = $this->provider->respond($this->systemPrompt($investor, $project), array_slice($history, 0, -1), $question);

            if ($result === null) {
                $this->usage->record($investor, AiInteractionType::InvestorAdvisor, AiRequestStatus::Failed, $project);
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => "Je n'ai pas pu générer de réponse pour le moment. Réessayez dans quelques instants.",
                ]);

                return $conversation->fresh('messages');
            }

            $this->usage->record($investor, AiInteractionType::InvestorAdvisor, AiRequestStatus::Completed, $project, config('ai.features.provider'), $result->tokensUsed);

            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $result->text,
                'tokens_used' => $result->tokensUsed,
            ]);

            return $conversation->fresh('messages');
        });
    }

    private function systemPrompt(User $investor, Project $project): string
    {
        $profile = $this->profileBlock($investor->investorProfile);

        return <<<PROMPT
            Tu es un coach en investissement qui accompagne un investisseur
            dans sa réflexion sur un projet entrepreneurial basé en
            République Démocratique du Congo, intitulé « {$project->title} ».

            RÈGLE ABSOLUE (spec §54-55, §148) : tu ne prends JAMAIS la
            décision d'investissement à la place de l'investisseur. Tu
            n'affirmes jamais qu'un projet "va réussir" ou "est un bon
            investissement" de façon catégorique — tu exposes les
            arbitrages (risques, signaux positifs, questions à poser,
            points de vigilance) pour éclairer sa propre décision.

            En plus de l'analyse du projet, aide-le à orienter ses propres
            choix, toujours au conditionnel et rapportés à son profil :
            - le montant / ticket cohérent avec sa fourchette et le besoin
              de financement du projet ;
            - la structure de l'engagement : financer le projet lui-même
              vs. prendre une participation au capital de la société, et
              les implications de chaque option ;
            - le niveau d'implication en gestion / gouvernance /
              accompagnement qui serait pertinent ;
            - l'adéquation (ou l'écart) entre ce projet et sa thèse, son
              horizon, son appétence au risque et ses secteurs/stades
              préférés.

            {$profile}

            Distingue toujours les faits fournis sur le projet de tes
            propres suppositions. Si une information manque pour répondre
            correctement, dis-le explicitement plutôt que de deviner.
            PROMPT;
    }

    private function profileBlock(?InvestorProfile $profile): string
    {
        if ($profile === null) {
            return "Profil de l'investisseur : non renseigné — invite-le à compléter son profil pour des conseils mieux ciblés.";
        }

        $lines = array_filter([
            'Type' => $profile->investor_type?->value,
            'Instrument privilégié' => $profile->funding_type?->value,
            'Horizon' => $profile->investment_horizon?->value,
            'Appétence au risque' => $profile->risk_appetite?->value,
            'Fourchette de ticket' => $profile->ticketRangeLabel(),
            'Zones géographiques' => filled($profile->preferred_geographies) ? implode(', ', $profile->preferred_geographies) : null,
            'Secteurs préférés' => $profile->sectors->pluck('name')->implode(', ') ?: null,
            'Stades préférés' => $profile->stages->pluck('name')->implode(', ') ?: null,
            'Recherche de l\'impact' => $profile->impact_focused ? 'oui' : null,
            'Thèse d\'investissement' => $profile->investment_thesis,
        ], fn ($v) => filled($v));

        if ($lines === []) {
            return "Profil de l'investisseur : créé mais quasi vide — signale-le et reste général.";
        }

        $rendered = collect($lines)->map(fn ($v, $k) => "- {$k} : {$v}")->implode("\n");

        return "Profil de l'investisseur (base tes recommandations dessus) :\n{$rendered}";
    }
}
