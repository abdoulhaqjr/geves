{{-- Contrôles visio d'une session (back-office). Attend : $s (EventSession) --}}
@php($st = $s->meetingStatusEnum())
@php($vis = $s->meetingVisibilityEnum())
@php($prov = $s->meeting_provider ?: config('meetings.default', 'livekit'))
<div x-data="{ open: false, prov: '{{ $prov }}' }"
     style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--ukc-border);">
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
        <span style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Visio</span>
        <span class="ukc-pill ukc-pill--{{ $st->pill() }}">{{ $st->label() }}</span>

        <form method="POST" action="{{ route('admin.program.sessions.meeting-status', $s) }}" style="display:inline-flex;gap:4px;flex-wrap:wrap;">
            @csrf
            @foreach (['scheduled' => 'Programmer', 'live' => 'En direct', 'ended' => 'Terminer', 'none' => 'Aucune'] as $val => $lbl)
                <button type="submit" name="status" value="{{ $val }}"
                        @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--blue' => $st->value === $val, 'ukc-btn--ghost' => $st->value !== $val])>
                    {{ $lbl }}
                </button>
            @endforeach
        </form>

        <span style="width:1px;height:20px;background:var(--ukc-border);"></span>

        <span style="font-size:.72rem;color:var(--ukc-muted);">Accès</span>
        <form method="POST" action="{{ route('admin.program.sessions.meeting-visibility', $s) }}" style="display:inline-flex;gap:4px;">
            @csrf
            @foreach (['public' => 'Public', 'participants' => 'Participants'] as $val => $lbl)
                <button type="submit" name="visibility" value="{{ $val }}"
                        @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--blue' => $vis->value === $val, 'ukc-btn--ghost' => $vis->value !== $val])>
                    {{ $lbl }}
                </button>
            @endforeach
        </form>

        @if ($s->isLive())
            <a href="{{ route('session.meeting', $s) }}" target="_blank" class="ukc-btn ukc-btn--green ukc-btn--sm">
                <x-ukc.icon name="video" :size="14" /> Rejoindre (modérateur)
            </a>
        @endif

        <button type="button" @click="open = !open" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="settings" :size="14" /> Salle
        </button>

        {{-- Résumé de la configuration --}}
        <span style="font-size:.75rem;color:var(--ukc-muted);">
            @if ($prov === 'custom')
                Lien externe{{ filled($s->meeting_url) ? '' : ' (non renseigné)' }}
            @else
                {{ $prov === 'livekit' ? 'LiveKit' : 'Jitsi' }} - salle {{ filled($s->meeting_room) ? '« '.$s->meeting_room.' »' : 'auto' }}
            @endif
        </span>
    </div>

    <div x-show="open" x-cloak style="margin-top:8px;padding:12px;border:1px solid var(--ukc-border);border-radius:10px;background:#fff;">
        <form method="POST" action="{{ route('admin.program.sessions.meeting', $s) }}">
            @csrf
            <div class="ukc-form-grid">
                <div class="ukc-field">
                    <label for="mProvider-{{ $s->id }}">Fournisseur</label>
                    <select id="mProvider-{{ $s->id }}" name="mProvider" x-model="prov">
                        <option value="livekit">LiveKit</option>
                        <option value="jitsi">Jitsi (intégré)</option>
                        <option value="custom">Lien externe (Zoom, Meet…)</option>
                    </select>
                </div>

                <div class="ukc-field" x-show="prov !== 'custom'">
                    <label for="mRoom-{{ $s->id }}">Nom de salle <span class="hint">(vide = auto)</span></label>
                    <input type="text" id="mRoom-{{ $s->id }}" name="mRoom" value="{{ $s->meeting_room }}" placeholder="colloque-pleniere-1">
                    @error('mRoom') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field full" x-show="prov === 'custom'">
                    <label for="mUrl-{{ $s->id }}">Lien de réunion <span class="req">*</span></label>
                    <input type="url" id="mUrl-{{ $s->id }}" name="mUrl" value="{{ $s->meeting_url }}" placeholder="https://zoom.us/j/…">
                    @error('mUrl') <span class="error">{{ $message }}</span> @enderror
                    <span class="hint">Ouvert dans un nouvel onglet (ces services bloquent l’intégration).</span>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:10px;">
                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                    <x-ukc.icon name="check" :size="14" /> Enregistrer
                </button>
                <button type="button" @click="open = false" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Annuler</button>
            </div>
        </form>
    </div>
</div>
