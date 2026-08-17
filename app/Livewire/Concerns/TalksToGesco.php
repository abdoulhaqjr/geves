<?php

namespace App\Livewire\Concerns;

use App\Actions\AskGesco;
use App\Models\Edition;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Logique de conversation avec l'assistante Gesco, partagée par la page dédiée
 * (`GescoChat`) et le widget flottant (`GescoWidget`). Conversation en mémoire
 * de composant ; anti-abus par throttle IP ; l'IA informe, n'exécute rien.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
trait TalksToGesco
{
    /** @var list<ChatTurn> */
    public array $messages = [];

    public string $input = '';

    /** Initialise la conversation avec un message d'accueil. */
    public function seedGesco(): void
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
            $answer = $ask->handle($history, $question, $this->activeGescoEdition());
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

    private function activeGescoEdition(): ?Edition
    {
        return Edition::with('colloque')->where('status', 'open')->latest('id')->first();
    }
}
