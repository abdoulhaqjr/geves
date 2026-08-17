<div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div class="ukc-field" style="max-width:320px;margin:0;">
            <label>Édition</label>
            <select wire:model.live="edition_id">
                <option value="">— Choisir —</option>
                @foreach ($editions as $ed)
                    <option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>
                @endforeach
            </select>
        </div>
        @if ($edition_id !== '')
            <div style="display:flex;gap:8px;">
                <a href="{{ route('admin.documents.sessions', $edition_id) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                    <x-ukc.icon name="file" :size="16" /> Fiches de session
                </a>
                <a href="{{ route('admin.program.slots') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                    <x-ukc.icon name="calendar" :size="16" /> Créneaux
                </a>
            </div>
        @endif
    </div>

    @if ($edition_id === '')
        <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer son programme.</p></div>
    @else
        <div class="ukc-grid-2" style="align-items:start;">
            {{-- Colonne gauche : contenu --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                {{-- Sessions plénières --}}
                <div class="ukc-card">
                    <h3><x-ukc.icon name="mic" :size="20" /> Sessions plénières</h3>
                    @forelse ($plenaries as $s)
                        <div wire:key="pl-{{ $s->id }}" style="padding:10px 0;border-bottom:1px solid var(--ukc-border);">
                            <div style="display:flex;justify-content:space-between;gap:10px;">
                                <div>
                                    <div style="font-weight:700;">{{ $s->label }}</div>
                                    <div style="font-size:.82rem;color:var(--ukc-muted);">
                                        {{ $s->session_day?->format('d/m/Y') }}
                                        @if ($s->starts_at) · {{ $s->starts_at->format('H:i') }}@if($s->ends_at)–{{ $s->ends_at->format('H:i') }}@endif @endif
                                        @if ($s->room) · {{ $s->room }} @endif
                                    </div>
                                </div>
                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="deleteSession({{ $s->id }})" wire:confirm="Supprimer ?"><x-ukc.icon name="trash" :size="14" /></button>
                            </div>
                            @include('livewire.admin.program._meeting', ['s' => $s])
                        </div>
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">Aucune session plénière.</p>
                    @endforelse
                </div>

                {{-- Ateliers --}}
                @foreach ($ateliers as $atelier)
                    <div wire:key="at-{{ $atelier->id }}" class="ukc-card">
                        <h3 style="justify-content:space-between;">
                            <span><x-ukc.icon name="layers" :size="20" /> {{ $atelier->title }}</span>
                            <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="deleteAtelier({{ $atelier->id }})" wire:confirm="Supprimer l'atelier ?"><x-ukc.icon name="trash" :size="14" /></button>
                        </h3>
                        @if (count($atelier->tracks ?? []))
                            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                                @foreach ($atelier->tracks as $t)<span class="ukc-pill ukc-pill--info">{{ $t }}</span>@endforeach
                            </div>
                        @endif
                        @forelse ($atelier->sessions as $s)
                            <div wire:key="as-{{ $s->id }}" style="padding:8px 0;border-bottom:1px solid var(--ukc-border);">
                                <div style="display:flex;justify-content:space-between;gap:10px;">
                                    <div>
                                        <div style="font-weight:600;font-size:.92rem;">{{ $s->label }}</div>
                                        <div style="font-size:.8rem;color:var(--ukc-muted);">
                                            {{ $s->session_day?->format('d/m/Y') }}
                                            @if ($s->room) · {{ $s->room }} @endif
                                            @if ($s->moderator_name) · Mod. {{ $s->moderator_name }} @endif
                                            @if ($s->rapporteur_name) · Rap. {{ $s->rapporteur_name }} @endif
                                        </div>
                                    </div>
                                    <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="deleteSession({{ $s->id }})" wire:confirm="Supprimer ?"><x-ukc.icon name="trash" :size="14" /></button>
                                </div>
                                @include('livewire.admin.program._meeting', ['s' => $s])
                            </div>
                        @empty
                            <p style="font-size:.85rem;color:var(--ukc-muted);">Aucune session dans cet atelier.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>

            {{-- Colonne droite : formulaires --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <h3><x-ukc.icon name="plus" :size="18" /> Nouvel atelier</h3>
                    <div class="ukc-field">
                        <label for="atelierTitle">Titre</label>
                        <input type="text" id="atelierTitle" wire:model="atelierTitle">
                        @error('atelierTitle') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    @if (count($this->tracks()))
                        <div class="ukc-field">
                            <label>Axes rattachés</label>
                            @foreach ($this->tracks() as $t)
                                <label style="display:flex;align-items:center;gap:8px;font-weight:500;font-size:.88rem;">
                                    <input type="checkbox" value="{{ $t }}" wire:model="atelierTracks" style="width:auto;padding:0;"> {{ $t }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:10px;" wire:click="addAtelier">Ajouter l'atelier</button>
                </div>

                <div class="ukc-card">
                    <h3><x-ukc.icon name="plus" :size="18" /> Nouvelle session</h3>
                    <div class="ukc-field">
                        <label for="sType">Type</label>
                        <select id="sType" wire:model.live="sType">
                            <option value="plenary">Plénière</option>
                            <option value="atelier">D'atelier</option>
                        </select>
                    </div>
                    @if ($sType === 'atelier')
                        <div class="ukc-field">
                            <label for="sAtelierId">Atelier</label>
                            <select id="sAtelierId" wire:model="sAtelierId">
                                <option value="">— Choisir —</option>
                                @foreach ($ateliers as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <div class="ukc-field">
                        <label for="sLabel">Intitulé</label>
                        <input type="text" id="sLabel" wire:model="sLabel">
                        @error('sLabel') <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-form-grid">
                        <div class="ukc-field"><label for="sDay">Jour</label><input type="date" id="sDay" wire:model="sDay"></div>
                        <div class="ukc-field"><label for="sRoom">Salle</label><input type="text" id="sRoom" wire:model="sRoom"></div>
                        <div class="ukc-field"><label for="sStart">Début</label><input type="time" id="sStart" wire:model="sStart">@error('sStart')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="ukc-field"><label for="sEnd">Fin</label><input type="time" id="sEnd" wire:model="sEnd">@error('sEnd')<span class="error">{{ $message }}</span>@enderror</div>
                        <div class="ukc-field"><label for="sModerator">Modérateur</label><input type="text" id="sModerator" wire:model="sModerator"></div>
                        <div class="ukc-field"><label for="sRapporteur">Rapporteur</label><input type="text" id="sRapporteur" wire:model="sRapporteur"></div>
                    </div>
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:10px;" wire:click="addSession">Ajouter la session</button>
                </div>
            </div>
        </div>
    @endif
</div>
