<x-layouts.admin title="Annonces" subtitle="Annonces du déroulement — publiées en direct sur la page « Déroulement »">
    <div class="admin-page admin-announcements">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:340px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.announcements') }}?edition_id='+this.value">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($edition && $edition->phase()->isOngoing())
                <span class="ukc-pill ukc-pill--ok"><x-ukc.icon name="dot" :size="12" /> Colloque en cours</span>
            @endif
        </div>

        @if ($edition_id === '' || ! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer ses annonces.</p></div>
        @else
            <div class="ukc-grid-2" style="align-items:start;gap:18px;">
                {{-- Nouvelle annonce --}}
                <div class="ukc-card">
                    <h2>Nouvelle annonce</h2>
                    <p style="font-size:13px;color:var(--ukc-muted);margin-bottom:14px;">Elle apparaît aussitôt en tête des annonces du déroulement, horodatée.</p>
                    <form method="POST" action="{{ route('admin.announcements.store', $edition) }}">
                        @csrf
                        <div class="ukc-field">
                            <label for="title">Titre <span class="req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="160" required
                                   placeholder="Ex. Changement de salle — Atelier 2">
                            @error('title') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ukc-field">
                            <label for="text">Détails</label>
                            <textarea id="text" name="text" rows="3" maxlength="2000"
                                      placeholder="Ex. La session Gouvernance se tient en Salle C (et non Salle B).">{{ old('text') }}</textarea>
                            @error('text') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <label class="ukc-check" style="display:flex;align-items:center;gap:8px;margin:4px 0 14px;cursor:pointer;">
                            <input type="checkbox" name="urgent" value="1" @checked(old('urgent'))>
                            <span>Marquer comme <strong>urgente</strong> (mise en avant)</span>
                        </label>
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="bell" :size="16" /> Publier l’annonce
                        </button>
                    </form>
                </div>

                {{-- Annonces publiées --}}
                <div class="ukc-card">
                    <h2>Annonces publiées <span class="ukc-badge-count">{{ count($announcements) }}</span></h2>
                    @forelse ($announcements as $a)
                        <div class="ann-row {{ ($a['urgent'] ?? false) ? 'is-urgent' : '' }}">
                            <div class="ann-row__main">
                                <div class="ann-row__head">
                                    <span class="ann-row__when">{{ $a['when'] ?? '' }}</span>
                                    @if ($a['urgent'] ?? false)<span class="ukc-pill ukc-pill--warn">Urgent</span>@endif
                                    <strong>{{ $a['title'] ?? '' }}</strong>
                                </div>
                                @if (filled($a['text'] ?? null))
                                    <p class="ann-row__text">{{ $a['text'] }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.announcements.destroy', [$edition, $a['id'] ?? '']) }}"
                                  onsubmit="return confirm('Retirer cette annonce ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ukc-btn--icon" title="Retirer" aria-label="Retirer l’annonce">
                                    <x-ukc.icon name="trash" :size="16" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p style="font-size:13.5px;color:var(--ukc-light);">Aucune annonce pour l’instant.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
