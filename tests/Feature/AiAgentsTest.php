<?php

use App\Support\AiAgents;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    // L'éditeur est un ONGLET des réglages, plus une page à part.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.settings', ['tab' => 'agents-ia']))
        ->assertOk()
        ->assertSee('Agents IA')
        ->assertSee('Gesco')
        ->assertSee('proposer', false);
});

it('ajoute puis enregistre un agent', function () {
    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [
            ['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants'],
            ['key' => 'admin', 'name' => 'Copilote', 'audience' => 'admin'],
            ['key' => 'rapports', 'name' => 'Agent Rapports', 'audience' => 'admin'],
        ],
    ])->assertSessionHasNoErrors();

    expect(collect(AiAgents::all())->pluck('name'))->toContain('Agent Rapports');
});

it('exige un nom d’agent', function () {
    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [['key' => 'gesco', 'name' => '', 'audience' => 'participants']],
    ])->assertSessionHasErrors('agents.0.name');
});

it('supprime un agent', function () {
    // La suppression est côté client ; le serveur persiste la liste soumise.
    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [
            ['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants'],
            ['key' => 'admin', 'name' => 'Copilote', 'audience' => 'admin'],
        ],
    ])->assertSessionHasNoErrors();

    expect(AiAgents::all())->toHaveCount(2);
});

it('téléverse un avatar d’agent et le persiste', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants', 'avatar' => '']],
        'avatars' => [0 => UploadedFile::fake()->image('gesco.png', 120, 120)],
    ])->assertSessionHasNoErrors();

    $agent = AiAgents::all()[0];
    expect($agent['avatar'])->not->toBe('');
    Storage::disk('public')->assertExists($agent['avatar']);
});

it('rejette un avatar non-image', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants']],
        'avatars' => [0 => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf')],
    ])->assertSessionHasErrors('avatars.0');
});

it('retire l’avatar d’un agent', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')->put(route('admin.settings.agents.update'), [
        'agents' => [['key' => 'gesco', 'name' => 'Gesco', 'audience' => 'participants', 'avatar' => 'ai-agents/x.png']],
        'remove_avatar' => [0 => '1'],
    ])->assertSessionHasNoErrors();

    expect(AiAgents::all()[0]['avatar'])->toBe('');
});
