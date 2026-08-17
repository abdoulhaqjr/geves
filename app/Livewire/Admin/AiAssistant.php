<?php

namespace App\Livewire\Admin;

use App\Actions\AskAdmin;
use App\Models\Edition;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Copilote d'administration (chat) : assiste le staff par des synthèses et des
 * brouillons à partir des chiffres réels de l'édition active. Il propose ; la
 * décision et l'exécution restent humaines. Consomme l'agent « admin ».
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
#[Title('Assistant IA')]
class AiAssistant extends Component
{
    /** @var list<ChatTurn> */
    public array $messages = [];

    public string $input = '';

    public function mount(): void
    {
        $this->messages = [[
            'role' => 'bot',
            'text' => 'Bonjour, je suis le copilote d’administration. Je peux résumer l’état de l’édition, '
                .'rédiger des brouillons (e-mails, synthèses) et signaler des points de vigilance. Je propose — vous décidez.',
        ]];
    }

    public function send(AskAdmin $ask): void
    {
        $question = trim($this->input);
        $this->validate(['input' => ['required', 'string', 'max:1500']]);

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

    public function ask(string $text, AskAdmin $ask): void
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
        return view('livewire.admin.ai-assistant')
            ->layout('components.layouts.admin', ['title' => 'Assistant IA']);
    }
}
