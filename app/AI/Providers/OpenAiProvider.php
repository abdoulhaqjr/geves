<?php

namespace App\AI\Providers;

/** OpenAI (chat/completions). */
class OpenAiProvider extends OpenAiCompatibleProvider
{
    public function key(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    protected function service(): string
    {
        return 'openai';
    }

    protected function endpoint(): string
    {
        return 'https://api.openai.com/v1/chat/completions';
    }

    protected function fallbackModel(): string
    {
        return 'gpt-4o';
    }
}
