<x-layouts.admin title="Programme" subtitle="Ateliers · Sessions · Modérateurs & rapporteurs">
    <div class="admin-page admin-program">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:320px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.program.sessions') }}?edition_id='+this.value">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($edition_id !== '')
                <div style="display:flex;gap:8px;">
                    <a href="{{ route('admin.documents.sessions', $edition_id) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                        <x-ukc.icon name="file" :size="16" /> Fiches de session
                    </a>
                    <a href="{{ route('admin.program.slots') }}?edition_id={{ $edition_id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="calendar" :size="16" /> Créneaux
                    </a>
                </div>
            @endif
        </div>

        @if ($edition_id === '')
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer son programme.</p></div>
        @else
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="grid" :size="20" /></div><div class="lbl">Ateliers</div><div class="val blue">{{ $stats['ateliers'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="layers" :size="20" /></div><div class="lbl">Sessions</div><div class="val">{{ $stats['sessions'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--slate"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">Créneaux</div><div class="val">{{ $stats['slots'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="calendar-check" :size="20" /></div><div class="lbl">Communications programmées</div><div class="val green">{{ $stats['comms'] }}</div></div>
            </div>
            <div class="ukc-grid-2" style="align-items:start;">
                {{-- Colonne gauche : contenu --}}
                <div style="display:flex;flex-direction:column;gap:18px;">
                    {{-- Sessions plénières --}}
                    <div class="ukc-card">
                        <h3><x-ukc.icon name="mic" :size="20" /> Sessions plénières</h3>
                        @forelse ($plenaries as $s)
                            <div style="padding:10px 0;border-bottom:1px solid var(--ukc-border);">
                                <div style="display:flex;justify-content:space-between;gap:10px;">
                                    <div>
                                        <div style="font-weight:700;">{{ $s->label }}</div>
                                        <div style="font-size:.82rem;color:var(--ukc-muted);">
                                            {{ $s->session_day?->format('d/m/Y') }}
                                            @if ($s->starts_at) · {{ $s->starts_at->format('H:i') }}@if($s->ends_at)–{{ $s->ends_at->format('H:i') }}@endif @endif
                                            @if ($s->room) · {{ $s->room }} @endif
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.program.sessions.destroy', $s) }}" onsubmit="return confirm('Supprimer ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="14" /></button>
                                    </form>
                                </div>
                                @include('admin.program._meeting', ['s' => $s])
                            </div>
                        @empty
                            <p style="font-size:.88rem;color:var(--ukc-muted);">Aucune session plénière.</p>
                        @endforelse
                    </div>

                    {{-- Ateliers --}}
                    @foreach ($ateliers as $atelier)
                        <div class="ukc-card">
                            <h3 style="justify-content:space-between;">
                                <span><x-ukc.icon name="layers" :size="20" /> {{ $atelier->title }}</span>
                                <form method="POST" action="{{ route('admin.program.ateliers.destroy', $atelier) }}" onsubmit="return confirm('Supprimer l\'atelier ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="14" /></button>
                                </form>
                            </h3>
                            @if (count($atelier->tracks ?? []))
                                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                                    @foreach ($atelier->tracks as $t)<span class="ukc-pill ukc-pill--info">{{ $t }}</span>@endforeach
                                </div>
                            @endif
                            @forelse ($atelier->sessions as $s)
                                <div style="padding:8px 0;border-bottom:1px solid var(--ukc-border);">
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
                                        <form method="POST" action="{{ route('admin.program.sessions.destroy', $s) }}" onsubmit="return confirm('Supprimer ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="14" /></button>
                                        </form>
                                    </div>
                                    @include('admin.program._meeting', ['s' => $s])
                                </div>
                            @empty
                                <p style="font-size:.85rem;color:var(--ukc-muted);">Aucune session dans cet atelier.</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>

                {{-- Colonne droite : formulaires --}}
                <div style="display:flex;flex-direction:column;gap:18px;">
                    <form method="POST" action="{{ route('admin.program.ateliers.store') }}" class="ukc-card">
                        @csrf
                        <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                        <h3><x-ukc.icon name="plus" :size="18" /> Nouvel atelier</h3>
                        <div class="ukc-field">
                            <label for="atelierTitle">Titre</label>
                            <input type="text" id="atelierTitle" name="atelierTitle" value="{{ old('atelierTitle') }}">
                            @error('atelierTitle') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        @if (count($tracks))
                            <div class="ukc-field">
                                <label>Axes rattachés</label>
                                @foreach ($tracks as $t)
                                    <label style="display:flex;align-items:center;gap:8px;font-weight:500;font-size:.88rem;">
                                        <input type="checkbox" name="atelierTracks[]" value="{{ $t }}" style="width:auto;padding:0;"> {{ $t }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:10px;">Ajouter l'atelier</button>
                    </form>

                    <form method="POST" action="{{ route('admin.program.sessions.store') }}" class="ukc-card" x-data="{ type: '{{ old('sType', 'plenary') }}' }">
                        @csrf
                        <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                        <h3><x-ukc.icon name="plus" :size="18" /> Nouvelle session</h3>
                        <div class="ukc-field">
                            <label for="sType">Type</label>
                            <select id="sType" name="sType" x-model="type">
                                <option value="plenary">Plénière</option>
                                <option value="atelier">D'atelier</option>
                            </select>
                        </div>
                        <div class="ukc-field" x-show="type === 'atelier'">
                            <label for="sAtelierId">Atelier</label>
                            <select id="sAtelierId" name="sAtelierId">
                                <option value="">— Choisir —</option>
                                @foreach ($ateliers as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach
                            </select>
                        </div>
                        <div class="ukc-field">
                            <label for="sLabel">Intitulé</label>
                            <input type="text" id="sLabel" name="sLabel" value="{{ old('sLabel') }}">
                            @error('sLabel') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-form-grid">
                            <div class="ukc-field"><label for="sDay">Jour</label><input type="date" id="sDay" name="sDay" value="{{ old('sDay') }}"></div>
                            <div class="ukc-field"><label for="sRoom">Salle</label><input type="text" id="sRoom" name="sRoom" value="{{ old('sRoom') }}"></div>
                            <div class="ukc-field"><label for="sStart">Début</label><input type="time" id="sStart" name="sStart" value="{{ old('sStart') }}">@error('sStart')<span class="error">{{ $message }}</span>@enderror</div>
                            <div class="ukc-field"><label for="sEnd">Fin</label><input type="time" id="sEnd" name="sEnd" value="{{ old('sEnd') }}">@error('sEnd')<span class="error">{{ $message }}</span>@enderror</div>
                            <div class="ukc-field"><label for="sModerator">Modérateur</label><input type="text" id="sModerator" name="sModerator" value="{{ old('sModerator') }}"></div>
                            <div class="ukc-field"><label for="sRapporteur">Rapporteur</label><input type="text" id="sRapporteur" name="sRapporteur" value="{{ old('sRapporteur') }}"></div>
                        </div>
                        <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:10px;">Ajouter la session</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
