<?php

namespace App\Services\Compliance\Data;

/**
 * Résultat structuré d'une évaluation de conformité pour une étape
 * contractuelle ou d'investissement. Les issues critiques bloquent,
 * les autres restent des avertissements consultables.
 */
final readonly class ComplianceGateResult
{
    /**
     * @param  list<ComplianceGateIssue>  $issues
     */
    public function __construct(
        public array $issues = [],
    ) {}

    public static function allow(): self
    {
        return new self;
    }

    /**
     * @param  list<ComplianceGateIssue>  $issues
     */
    public static function of(array $issues): self
    {
        return new self($issues);
    }

    public function merge(self ...$results): self
    {
        $issues = $this->issues;

        foreach ($results as $result) {
            $issues = array_merge($issues, $result->issues);
        }

        return new self($issues);
    }

    public function allowed(): bool
    {
        return $this->blockingIssues() === [];
    }

    /**
     * @return list<ComplianceGateIssue>
     */
    public function blockingIssues(): array
    {
        return array_values(array_filter(
            $this->issues,
            fn (ComplianceGateIssue $issue): bool => $issue->blocks(),
        ));
    }

    /**
     * @return list<ComplianceGateIssue>
     */
    public function warnings(): array
    {
        return array_values(array_filter(
            $this->issues,
            fn (ComplianceGateIssue $issue): bool => ! $issue->blocks(),
        ));
    }

    /**
     * @return list<string>
     */
    public function blockingMessages(): array
    {
        return array_map(
            fn (ComplianceGateIssue $issue): string => $issue->message,
            $this->blockingIssues(),
        );
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_map(
            fn (ComplianceGateIssue $issue): string => $issue->code,
            $this->issues,
        );
    }

    /**
     * @return array{allowed: bool, issues: list<array{code: string, message: string, severity: string, subject: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed(),
            'issues' => array_map(
                fn (ComplianceGateIssue $issue): array => $issue->toArray(),
                $this->issues,
            ),
        ];
    }
}
