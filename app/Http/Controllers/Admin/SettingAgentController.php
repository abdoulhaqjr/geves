<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AiAgents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Réglages « Agents IA » : persona et garde-fous
 * (instructions POSITIVES / NÉGATIVES) de chaque agent, extensible. Les agents,
 * onglets et avatars sont gérés côté client (Alpine). Persisté via {@see AiAgents}
 * (réglage `ai.agents`).
 *
 * ⚠️ Avatar : d'abord CHOISI dans la galerie fournie ({@see AiAgents::GALLERY},
 * `marc-*` / `awa-*`) ; l'admin peut aussi TÉLÉVERSER son image via la tuile « + »
 * (stockée sur le disque `public`, dossier `ai-agents`). On stocke donc soit un
 * chemin de galerie (`img/agents/…`), soit un chemin de téléversement
 * (`ai-agents/…`), soit vide.
 *
 * ⚠️ Plus de page dédiée : l'éditeur est l'onglet `?tab=agents-ia` des réglages
 * (vue `admin/settings/_agents.blade.php`). Seul l'enregistrement vit ici.
 */
class SettingAgentController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // L'avatar (champ texte) doit être un chemin de la galerie, vide, OU un
        // avatar DÉJÀ enregistré (tolère un ancien téléversement non encore remplacé).
        // ⚠️ La tolérance vient des agents STOCKÉS, jamais de la requête - sinon
        // n'importe quelle valeur postée s'autoriserait elle-même. Un fichier envoyé
        // via la tuile « + » (clé `avatars.$i`) l'emporte ensuite sur ce champ.
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
            // ⚠️ Pas de SVG (servi depuis notre domaine, peut porter du script).
            'avatars.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [], ['agents.*.name' => 'nom de l’agent']);

        // Avatars précédents (par clé) : pour effacer un ancien FICHIER devenu orphelin.
        $previous = collect(AiAgents::all())->keyBy('key');

        $agents = [];
        foreach ((array) $request->input('agents', []) as $i => $a) {
            $key = (string) ($a['key'] ?? ('agent-'.Str::lower(Str::random(4))));
            $avatar = (string) ($a['avatar'] ?? '');

            // Image personnalisée téléversée : elle l'emporte sur le choix de galerie.
            if ($request->hasFile("avatars.$i")) {
                $avatar = (string) $request->file("avatars.$i")->store('ai-agents', 'public');
            }

            // Si l'avatar a changé et que l'ancien était un FICHIER téléversé, on le
            // supprime (un chemin de galerie `img/…` n'est jamais effacé).
            $old = (string) ($previous[$key]['avatar'] ?? '');
            if ($old !== $avatar && str_starts_with($old, 'ai-agents/')) {
                $this->deleteUpload($old);
            }

            $agents[] = [
                'key' => $key,
                'name' => (string) ($a['name'] ?? ''),
                'audience' => in_array($a['audience'] ?? '', ['participants', 'admin'], true) ? $a['audience'] : 'participants',
                'enabled' => $request->boolean("agents.$i.enabled"),
                'avatar' => $avatar,
                'persona' => (string) ($a['persona'] ?? ''),
                'positive' => (string) ($a['positive'] ?? ''),
                'negative' => (string) ($a['negative'] ?? ''),
            ];
        }

        AiAgents::save($agents);

        return back()->with('ukc_status', 'Agents IA enregistrés.');
    }

    /** Supprime un avatar téléversé du disque `public` (jamais une image de galerie). */
    private function deleteUpload(string $path): void
    {
        if (str_starts_with($path, 'ai-agents/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
