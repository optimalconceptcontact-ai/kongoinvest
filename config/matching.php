<?php

return [
    // Off by default: rule-only matching works completely on its own
    // (spec §23's hybrid architecture explicitly says "ne pas dépendre
    // uniquement d'un LLM"). Flip on once ANTHROPIC_API_KEY is set and
    // you've decided you want the 3 narrative criteria AI-scored instead
    // of the neutral completeness fallback.
    'ai_enabled' => env('MATCHING_AI_ENABLED', false),
];
