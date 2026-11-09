<?php

namespace App\Contracts\Compliance;

interface AmlScreeningProviderContract
{
    /**
     * Screen an individual or entity against PEP, sanctions, and adverse media lists.
     *
     * @return array{
     *     provider: string,
     *     pep: array{
     *         has_match: bool,
     *         match_type: ?string,
     *         match_score: float,
     *         role_title: ?string,
     *         jurisdiction: ?string,
     *         matches: array<mixed>
     *     },
     *     sanctions: array{
     *         has_match: bool,
     *         match_score: float,
     *         matched_lists: array<string>,
     *         matches: array<mixed>
     *     },
     *     adverse_media: array{
     *         has_hits: bool,
     *         hit_count: int,
     *         hits: array<mixed>
     *     }
     * }
     */
    public function screen(string $fullName, string $country = 'CD', string $subjectType = 'individual'): array;
}
