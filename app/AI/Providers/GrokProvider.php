<?php

namespace App\AI\Providers;

/** Grok (xAI) — API compatible OpenAI. */
class GrokProvider extends OpenAiCompatibleProvider
{
    public function key(): string
    {
        return 'grok';
    }

    public function label(): string
    {
        return 'Grok (xAI)';
    }

    protected function service(): string
    {
        return 'grok';
    }

    protected function endpoint(): string
    {
        return 'https://api.x.ai/v1/chat/completions';
    }

    protected function fallbackModel(): string
    {
        return 'grok-4';
    }
}
