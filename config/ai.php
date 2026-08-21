<?php

return [
    // Off by default, same reasoning as matching.php's toggle: every AI
    // feature (project analysis, coach, advisor) degrades to a clean
    // "unavailable, try again later" state rather than crashing when
    // this is false or no ANTHROPIC_API_KEY is set (spec §113).
    'enabled' => env('AI_FEATURES_ENABLED', false),
];
