<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AiAgents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Réglages « Agents IA » : persona et garde-fous
 * (instructions POSITIVES / NÉGATIVES) de chaque agent, extensible. Les agents,
 * onglets et avatars sont gérés côté client (Alpine). Persisté via {@see AiAgents}
 * (réglage `ai.agents`).
 *
 * ⚠️ L'avatar n'est plus téléversé depuis l'ordinateur : il est CHOISI dans la
 * galerie fournie ({@see AiAgents::GALLERY}, `marc-*` / `awa-*`). On ne stocke donc
 * qu'un chemin de la galerie (ou vide). Un ANCIEN avatar téléversé resté en base
 * est conservé tel quel tant qu'il n'est pas remplacé.
 *
 * ⚠️ Plus de page dédiée : l'éditeur est l'onglet `?tab=agents-ia` des réglages
 * (vue `admin/settings/_agents.blade.php`). Seul l'enregistrement vit ici.
 */
class SettingAgentController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // L'avatar doit être un chemin de la galerie, vide, OU un avatar DÉJÀ
        // enregistré (tolère un ancien téléversement non encore remplacé). ⚠️ La
        // tolérance vient des agents STOCKÉS, jamais de la requête - sinon n'importe
        // quelle valeur postée s'autoriserait elle-même.
        $allowed = array_merge(AiAgents::galleryPaths(), [''], array_filter(array_map(
            fn (array $a) => (string) $a['avatar'],
            AiAgents::all(),
        )));

        $request->validate([
            'agents' => ['array', 'min:1'],
            'agents.*.name' => ['required', 'string', 'max:80'],
            'agents.*.audience' => ['required', 'in:participants,admin'],
            'agents.*.avatar' => ['nullable', 'string', 'in:'.implode(',', array_unique($allowed))],
            'agents.*.persona' => ['nullable', 'string', 'max:4000'],
            'agents.*.positive' => ['nullable', 'string', 'max:4000'],
            'agents.*.negative' => ['nullable', 'string', 'max:4000'],
        ], [], ['agents.*.name' => 'nom de l’agent']);

        $agents = [];
        foreach ((array) $request->input('agents', []) as $i => $a) {
            $agents[] = [
                'key' => (string) ($a['key'] ?? ('agent-'.Str::lower(Str::random(4)))),
                'name' => (string) ($a['name'] ?? ''),
                'audience' => in_array($a['audience'] ?? '', ['participants', 'admin'], true) ? $a['audience'] : 'participants',
                'enabled' => $request->boolean("agents.$i.enabled"),
                'avatar' => (string) ($a['avatar'] ?? ''),
                'persona' => (string) ($a['persona'] ?? ''),
                'positive' => (string) ($a['positive'] ?? ''),
                'negative' => (string) ($a['negative'] ?? ''),
            ];
        }

        AiAgents::save($agents);

        return back()->with('ukc_status', 'Agents IA enregistrés.');
    }
}
