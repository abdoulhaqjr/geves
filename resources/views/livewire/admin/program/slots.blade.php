<div>
    <div class="ukc-field" style="max-width:320px;margin-bottom:18px;">
        <label>Édition</label>
        <select wire:model.live="edition_id">
            <option value="">— Choisir —</option>
            @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
        </select>
    </div>

    @if ($edition_id === '')
        <div class="ukc-empty"><h3>Choisissez une édition</h3></div>
    @else
        <div class="ukc-grid-2" style="align-items:start;">
            <div class="ukc-card">
                <h3><x-ukc.icon name="calendar" :size="20" /> Créneaux</h3>
                @forelse ($creneaux as $slot)
                    <div wire:key="slot-{{ $slot->id }}" style="padding:12px 0;border-bottom:1px solid var(--ukc-border);">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div style="font-size:.85rem;color:var(--ukc-muted);">
                                {{ $slot->session_label ?: 'Sans session' }}
                                @if ($slot->room) · {{ $slot->room }} @endif
                                @if ($slot->starts_at) · {{ $slot->starts_at->format('d/m H:i') }} @endif
                            </div>
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="deleteSlot({{ $slot->id }})" wire:confirm="Supprimer ?"><x-ukc.icon name="trash" :size="14" /></button>
                        </div>
                        <div class="ukc-field" style="margin-top:8px;">
                            <select wire:change="assign({{ $slot->id }}, $event.target.value)">
                                <option value="">— Non assigné —</option>
                                @foreach ($accepted as $sub)
                                    <option value="{{ $sub->id }}" @selected($slot->submission_id === $sub->id)>
                                        {{ $sub->ref_code }} — {{ \Illuminate\Support\Str::limit($sub->title, 45) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @empty
                    <p style="font-size:.88rem;color:var(--ukc-muted);">Aucun créneau. Créez-en un à droite.</p>
                @endforelse
            </div>

            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h3><x-ukc.icon name="plus" :size="18" /> Nouveau créneau</h3>
                    <div class="ukc-field">
                        <label for="slSessionId">Session</label>
                        <select id="slSessionId" wire:model="slSessionId">
                            <option value="">— Aucune —</option>
                            @foreach ($sessions as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ukc-form-grid">
                        <div class="ukc-field"><label for="slDay">Jour</label><input type="date" id="slDay" wire:model="slDay"></div>
                        <div class="ukc-field"><label for="slStart">Heure</label><input type="time" id="slStart" wire:model="slStart">@error('slStart')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="ukc-field full"><label for="slRoom">Salle</label><input type="text" id="slRoom" wire:model="slRoom"></div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:10px;" wire:click="addSlot">Ajouter le créneau</button>
                </div>

                <div class="ukc-card">
                    <h3><x-ukc.icon name="check-circle" :size="18" /> Communications acceptées</h3>
                    <p style="font-size:.85rem;color:var(--ukc-muted);margin-bottom:8px;">{{ $accepted->count() }} acceptée(s).</p>
                    @foreach ($accepted as $sub)
                        @php($slot = $creneaux->firstWhere('submission_id', $sub->id))
                        <div wire:key="acc-{{ $sub->id }}" style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;font-size:.85rem;border-bottom:1px solid var(--ukc-border);">
                            <span>{{ $sub->ref_code }}</span>
                            @if ($slot)<span class="ukc-pill ukc-pill--ok">Programmée</span>@else<span class="ukc-pill ukc-pill--muted">À placer</span>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
