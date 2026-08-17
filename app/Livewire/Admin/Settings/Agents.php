<?php

namespace App\Livewire\Admin\Settings;

use App\Support\AiAgents;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Réglages « Agents IA » : personnalité (persona) et garde-fous (instructions
 * POSITIVES / NÉGATIVES) de chaque agent, extensible. Rappel affiché : les
 * agents d'administration ne font que **proposer** — la décision reste humaine.
 * Persisté via {@see AiAgents} (réglages `ai.agents`).
 */
#[Title('Agents IA')]
class Agents extends Component
{
    /** @var list<array<string, mixed>> */
    public array $agents = [];

    public int $active = 0;

    public function mount(): void
    {
        $this->agents = AiAgents::all();
    }

    public function addAgent(): void
    {
        $this->agents[] = [
            'key' => 'agent-'.Str::lower(Str::random(4)),
            'name' => 'Nouvel agent',
            'audience' => 'participants',
            'enabled' => true,
            'persona' => '',
            'positive' => '',
            'negative' => '',
        ];
        $this->active = count($this->agents) - 1;
    }

    public function removeAgent(int $i): void
    {
        if (! isset($this->agents[$i])) {
            return;
        }

        unset($this->agents[$i]);
        $this->agents = array_values($this->agents);
        $this->active = max(0, min($this->active, count($this->agents) - 1));
    }

    public function save(): void
    {
        $this->validate([
            'agents' => ['array', 'min:1'],
            'agents.*.name' => ['required', 'string', 'max:80'],
            'agents.*.audience' => ['required', 'in:participants,admin'],
            'agents.*.persona' => ['nullable', 'string', 'max:4000'],
            'agents.*.positive' => ['nullable', 'string', 'max:4000'],
            'agents.*.negative' => ['nullable', 'string', 'max:4000'],
        ], [], [
            'agents.*.name' => 'nom de l’agent',
        ]);

        AiAgents::save($this->agents);
        $this->agents = AiAgents::all();
        $this->active = min($this->active, count($this->agents) - 1);

        session()->flash('ukc_status', 'Agents IA enregistrés.');
    }

    public function render()
    {
        return view('livewire.admin.settings.agents')
            ->layout('components.layouts.admin', ['title' => 'Agents IA']);
    }
}
