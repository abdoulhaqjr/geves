<?php

return [
    // Aucun compte de bureau n’est requis : l’application utilise les clés API saisies dans Réglages.
    'node_binary' => env('TUTORIAL_VIDEO_NODE_BIN', 'node'),
    'npx_binary' => env('TUTORIAL_VIDEO_NPX_BIN', PHP_OS_FAMILY === 'Windows' ? 'npx.cmd' : 'npx'),
    'hyperframes_version' => env('TUTORIAL_VIDEO_HYPERFRAMES_VERSION', '0.8.142'),
    'render_timeout' => (int) env('TUTORIAL_VIDEO_RENDER_TIMEOUT', 3300),
    'capture_timeout' => (int) env('TUTORIAL_VIDEO_CAPTURE_TIMEOUT', 60000),
    'capture_process_timeout' => (int) env('TUTORIAL_VIDEO_CAPTURE_PROCESS_TIMEOUT', 240),
    'capture_budget' => (int) env('TUTORIAL_VIDEO_CAPTURE_BUDGET', 60000),
    'creative_tier' => env('TUTORIAL_VIDEO_CREATIVE_TIER', 'fast'),
];
