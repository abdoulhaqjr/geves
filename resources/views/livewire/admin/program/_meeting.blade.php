{{-- Contrôles visio d'une session (back-office). Attend : $s (EventSession) --}}
@php($st = $s->meetingStatusEnum())
@php($vis = $s->meetingVisibilityEnum())
<div style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--ukc-border);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
    <span style="font-size:.72rem;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Visio</span>
    <span class="ukc-pill ukc-pill--{{ $st->pill() }}">{{ $st->label() }}</span>

    <div style="display:inline-flex;gap:4px;flex-wrap:wrap;">
        @foreach (['scheduled' => 'Programmer', 'live' => 'En direct', 'ended' => 'Terminer', 'none' => 'Aucune'] as $val => $lbl)
            <button type="button" wire:click="setMeetingStatus({{ $s->id }}, '{{ $val }}')"
                    @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--blue' => $st->value === $val, 'ukc-btn--ghost' => $st->value !== $val])>
                {{ $lbl }}
            </button>
        @endforeach
    </div>

    <span style="width:1px;height:20px;background:var(--ukc-border);"></span>

    <span style="font-size:.72rem;color:var(--ukc-muted);">Accès</span>
    <div style="display:inline-flex;gap:4px;">
        @foreach (['public' => 'Public', 'participants' => 'Participants'] as $val => $lbl)
            <button type="button" wire:click="setMeetingVisibility({{ $s->id }}, '{{ $val }}')"
                    @class(['ukc-btn', 'ukc-btn--sm', 'ukc-btn--blue' => $vis->value === $val, 'ukc-btn--ghost' => $vis->value !== $val])>
                {{ $lbl }}
            </button>
        @endforeach
    </div>

    @if ($s->isLive())
        <a href="{{ route('session.meeting', $s) }}" target="_blank" class="ukc-btn ukc-btn--green ukc-btn--sm">
            <x-ukc.icon name="video" :size="14" /> Rejoindre (modérateur)
        </a>
    @endif

    <button type="button" wire:click="editMeeting({{ $s->id }})" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
        <x-ukc.icon name="settings" :size="14" /> Salle
    </button>

    {{-- Résumé de la configuration --}}
    @php($prov = $s->meeting_provider ?: config('meetings.default', 'livekit'))
    <span style="font-size:.75rem;color:var(--ukc-muted);">
        @if ($prov === 'custom')
            Lien externe{{ filled($s->meeting_url) ? '' : ' (non renseigné)' }}
        @else
            {{ $prov === 'livekit' ? 'LiveKit' : 'Jitsi' }} · salle {{ filled($s->meeting_room) ? '« '.$s->meeting_room.' »' : 'auto' }}
        @endif
    </span>
</div>

@if ($editingMeeting === $s->id)
    <div style="margin-top:8px;padding:12px;border:1px solid var(--ukc-border);border-radius:10px;background:#fff;">
        <div class="ukc-form-grid">
            <div class="ukc-field">
                <label for="mProvider-{{ $s->id }}">Fournisseur</label>
                <select id="mProvider-{{ $s->id }}" wire:model.live="mProvider">
                    <option value="livekit">LiveKit</option>
                    <option value="jitsi">Jitsi (intégré)</option>
                    <option value="custom">Lien externe (Zoom, Meet…)</option>
                </select>
            </div>

            @if (in_array($mProvider, ['jitsi', 'livekit']))
                <div class="ukc-field">
                    <label for="mRoom-{{ $s->id }}">Nom de salle <span class="hint">(vide = auto)</span></label>
                    <input type="text" id="mRoom-{{ $s->id }}" wire:model="mRoom" placeholder="colloque-pleniere-1">
                    @error('mRoom') <span class="error">{{ $message }}</span> @enderror
                </div>
            @else
                <div class="ukc-field full">
                    <label for="mUrl-{{ $s->id }}">Lien de réunion <span class="req">*</span></label>
                    <input type="url" id="mUrl-{{ $s->id }}" wire:model="mUrl" placeholder="https://zoom.us/j/…">
                    @error('mUrl') <span class="error">{{ $message }}</span> @enderror
                    <span class="hint">Ouvert dans un nouvel onglet (ces services bloquent l’intégration).</span>
                </div>
            @endif
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <button type="button" wire:click="saveMeeting" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                <x-ukc.icon name="check" :size="14" /> Enregistrer
            </button>
            <button type="button" wire:click="cancelMeetingEdit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Annuler</button>
        </div>
    </div>
@endif
