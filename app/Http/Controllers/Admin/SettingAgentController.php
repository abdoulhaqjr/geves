<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AiAgents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Réglages « Agents IA » (contrôleur + Blade) : persona et garde-fous
 * (instructions POSITIVES / NÉGATIVES) de chaque agent, extensible. Les agents,
 * onglets et avatars sont gérés côté client (Alpine) ; les avatars sont
 * téléversés à l'enregistrement. Persisté via {@see AiAgents} (réglage
 * `ai.agents`).
 */
class SettingAgentController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.agents', [
            'agents' => AiAgents::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'agents' => ['array', 'min:1'],
            'agents.*.name' => ['required', 'string', 'max:80'],
            'agents.*.audience' => ['required', 'in:participants,admin'],
            'agents.*.persona' => ['nullable', 'string', 'max:4000'],
            'agents.*.positive' => ['nullable', 'string', 'max:4000'],
            'agents.*.negative' => ['nullable', 'string', 'max:4000'],
            'avatars.*' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ], [], ['agents.*.name' => 'nom de l’agent']);

        $agents = [];
        foreach ((array) $request->input('agents', []) as $i => $a) {
            $avatar = (string) ($a['avatar'] ?? '');

            if ($request->boolean("remove_avatar.$i")) {
                $this->deleteAvatar($avatar);
                $avatar = '';
            } elseif ($request->hasFile("avatars.$i")) {
                $path = $request->file("avatars.$i")->store('ai-agents', 'public');
                $this->deleteAvatar($avatar);
                $avatar = $path;
            }

            $agents[] = [
                'key' => (string) ($a['key'] ?? ('agent-'.Str::lower(Str::random(4)))),
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

    private function deleteAvatar(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
