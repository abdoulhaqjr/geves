<?php

return [
    // Aucun compte de bureau n’est requis : l’application utilise les clés API saisies dans Réglages.
    'node_binary' => env('TUTORIAL_VIDEO_NODE_BIN', 'node'),
    'hyperframes_version' => env('TUTORIAL_VIDEO_HYPERFRAMES_VERSION', '0.8.142'),
    'render_timeout' => (int) env('TUTORIAL_VIDEO_RENDER_TIMEOUT', 3300),
    'creative_tier' => env('TUTORIAL_VIDEO_CREATIVE_TIER', 'fast'),
];
