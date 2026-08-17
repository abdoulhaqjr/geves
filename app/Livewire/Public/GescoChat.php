<?php

namespace App\Livewire\Public;

use App\Actions\AskGesco;
use App\Models\Edition;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Chat public avec l'assistante Gesco (soumissions, tarifs, programme,
 * documents…). Conversation en mémoire de composant ; l'IA informe et oriente
 * mais n'exécute aucune action. Anti-abus : throttle par IP.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
#[Title('Assistante Gesco')]
class GescoChat extends Component
{
    /** @var list<ChatTurn> */
    public array $messages = [];

    public string $input = '';

    public bool $pending = false;

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'bot',
            'text' => 'Bonjour, je suis Gesco, l’assistante des colloques de l’Université de Kara. '
                .'Posez-moi vos questions sur les soumissions, inscriptions, tarifs, programme ou documents.',
        ]];
    }

    public function send(AskGesco $ask): void
    {
        $question = trim($this->input);

        $this->validate(['input' => ['required', 'string', 'max:1000']]);

        $key = 'gesco:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->messages[] = ['role' => 'bot', 'text' => 'Vous avez envoyé beaucoup de messages. Merci de réessayer un peu plus tard.'];
            $this->input = '';

            return;
        }
        RateLimiter::hit($key, 3600);

        $history = $this->messages;
        $this->messages[] = ['role' => 'user', 'text' => $question];
        $this->input = '';

        try {
            $answer = $ask->handle($history, $question, $this->activeEdition());
        } catch (\Throwable $e) {
            $answer = $e->getMessage();
        }

        $this->messages[] = ['role' => 'bot', 'text' => $answer];
    }

    public function ask(string $text, AskGesco $ask): void
    {
        $this->input = $text;
        $this->send($ask);
    }

    private function activeEdition(): ?Edition
    {
        return Edition::with('colloque')->where('status', 'open')->latest('id')->first();
    }

    public function render()
    {
        return view('livewire.public.gesco-chat')
            ->layout('components.layouts.app', ['title' => 'Assistante Gesco']);
    }
}
