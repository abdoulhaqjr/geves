<?php

return [
    'codex_binary' => env('TUTORIAL_VIDEO_CODEX_BIN', 'codex'),
    // Gemini prépare le scénario ; Claude le contrôle lorsque leurs clés API sont configurées.
    'creative_tier' => env('TUTORIAL_VIDEO_CREATIVE_TIER', 'fast'),
];
