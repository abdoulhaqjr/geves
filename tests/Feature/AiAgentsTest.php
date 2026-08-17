<?php

use App\Livewire\Admin\Settings\Agents;
use App\Support\AiAgents;
use App\Support\Settings;
use Livewire\Livewire;

// adminStaff() -> défini dans les helpers de tests partagés

it('fournit des agents par défaut quand rien n’est configuré', function () {
    $agents = AiAgents::all();

    expect($agents)->not->toBeEmpty();
    expect(collect($agents)->pluck('key'))->toContain('gesco', 'admin');
    expect(AiAgents::find('gesco')['audience'])->toBe('participants');
    expect(AiAgents::find('admin')['audience'])->toBe('admin');
    expect(AiAgents::find('inconnu'))->toBeNull();
});

it('enregistre et relit les agents', function () {
    AiAgents::save([
        ['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants', 'enabled' => true,
            'persona' => 'Assistante des colloques.', 'positive' => '- Aider', 'negative' => '- Ne pas inventer'],
    ]);

    expect(Settings::get('ai.agents'))->toBeArray();
    $agents = AiAgents::all();
    expect($agents)->toHaveCount(1);
    expect($agents[0]['persona'])->toBe('Assistante des colloques.');
});

it('normalise une clé manquante depuis le nom', function () {
    AiAgents::save([['name' => 'Mon Agent Test', 'audience' => 'admin']]);

    expect(AiAgents::all()[0]['key'])->toBe('mon-agent-test');
    expect(AiAgents::all()[0]['audience'])->toBe('admin');
});

it('affiche l’éditeur d’agents au staff', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(Agents::class)
        ->assertOk()
        ->assertSee('Agents IA')
        ->assertSee('Gesco')
        ->assertSee('proposer', false);
});

it('ajoute puis enregistre un agent', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(Agents::class)
        ->call('addAgent')
        ->set('agents.2.name', 'Agent Rapports')
        ->set('agents.2.audience', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect(collect(AiAgents::all())->pluck('name'))->toContain('Agent Rapports');
});

it('exige un nom d’agent', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(Agents::class)
        ->set('agents.0.name', '')
        ->call('save')
        ->assertHasErrors('agents.0.name');
});

it('supprime un agent', function () {
    $test = Livewire::actingAs(adminStaff(), 'web')
        ->test(Agents::class)
        ->call('addAgent')
        ->call('removeAgent', 2)
        ->assertHasNoErrors();

    expect($test->get('agents'))->toHaveCount(2);
});
