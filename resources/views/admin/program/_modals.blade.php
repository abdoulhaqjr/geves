@php
    $roomOptions = collect($spaces)->pluck('name')->merge($usedRooms)->filter()->unique()->values();
@endphp

<datalist id="roomsList">
    @foreach ($roomOptions as $r)<option value="{{ $r }}">@endforeach
</datalist>

{{-- ===== Modale Atelier ===== --}}
<div class="ukc-modal" x-show="modal==='atelier'" x-cloak @click.self="modal=null" style="display:none;">
    <div class="box">
        <div class="box-head">
            <span x-text="form.id ? 'Modifier l’atelier' : 'Nouvel atelier'"></span>
            <button type="button" @click="modal=null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="box-body">
            <form method="POST" :action="form.id ? '{{ url('admin/programme/ateliers') }}/'+form.id : '{{ route('admin.program.ateliers.store') }}'">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                <div class="ukc-form-row">
                    <label>Intitulé de l’atelier</label>
                    <input type="text" name="atelierTitle" x-model="form.title" required placeholder="Ex. Atelier Formation &amp; pédagogie">
                </div>
                <div class="ukc-form-row">
                    <label>Axes thématiques</label>
                    @forelse ($tracks as $t)
                        <label class="ukc-check"><input type="checkbox" name="atelierTracks[]" value="{{ $t }}" x-model="form.tracks"> {{ $t }}</label>
                    @empty
                        <p class="ukc-hint">Aucun axe défini pour cette édition (réglés dans le formulaire d’édition).</p>
                    @endforelse
                </div>
                <div class="ukc-form-row" x-show="form.id">
                    <label>Salle associée (optionnel)</label>
                    <input type="text" name="atelierRoom" x-model="form.room" list="roomsList" placeholder="Ex. Salle A">
                </div>
                <div class="box-foot">
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
                    <button type="submit" class="ukc-btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modale Session ===== --}}
<div class="ukc-modal" x-show="modal==='session'" x-cloak @click.self="modal=null" style="display:none;">
    <div class="box">
        <div class="box-head">
            <span x-text="form.id ? 'Modifier la session' : 'Nouvelle session'"></span>
            <button type="button" @click="modal=null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="box-body">
            <form method="POST" :action="form.id ? '{{ url('admin/programme/sessions') }}/'+form.id : '{{ route('admin.program.sessions.store') }}'">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                <div class="ukc-form-row">
                    <label>Type</label>
                    <select name="sType" x-model="form.type">
                        <option value="atelier">Session d’atelier</option>
                        <option value="plenary">Plénière</option>
                    </select>
                </div>
                <div class="ukc-form-row" x-show="form.type==='atelier'">
                    <label>Atelier</label>
                    <select name="sAtelierId" x-model="form.atelier_id">
                        <option value="">- Choisir -</option>
                        @foreach ($ateliers as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-form-row">
                    <label>Intitulé de la session</label>
                    <input type="text" name="sLabel" x-model="form.label" required placeholder="Ex. Session 1 - Matin">
                </div>
                <div class="ukc-form-grid">
                    <div class="ukc-form-row"><label>Jour</label><input type="date" name="sDay" x-model="form.day"></div>
                    <div class="ukc-form-row"><label>Salle</label><input type="text" name="sRoom" x-model="form.room" list="roomsList"></div>
                </div>
                <div class="ukc-form-grid">
                    <div class="ukc-form-row"><label>Début</label><input type="time" name="sStart" x-model="form.start"></div>
                    <div class="ukc-form-row"><label>Fin</label><input type="time" name="sEnd" x-model="form.end"></div>
                </div>
                <div class="ukc-form-grid">
                    <div class="ukc-form-row"><label>Modérateur</label><input type="text" name="sModerator" x-model="form.moderator"></div>
                    <div class="ukc-form-row"><label>Rapporteur</label><input type="text" name="sRapporteur" x-model="form.rapporteur"></div>
                </div>
                <div class="box-foot">
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
                    <button type="submit" class="ukc-btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modale Créneau ===== --}}
<div class="ukc-modal" x-show="modal==='slot'" x-cloak @click.self="modal=null" style="display:none;">
    <div class="box">
        <div class="box-head">
            <span x-text="form.id ? 'Modifier le créneau' : 'Nouveau créneau'"></span>
            <button type="button" @click="modal=null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="box-body">
            <form method="POST" :action="form.id ? '{{ url('admin/programme/creneaux') }}/'+form.id : '{{ route('admin.program.slots.store') }}'">
                @csrf
                <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                <div class="ukc-form-row">
                    <label>Session</label>
                    <select name="slSessionId" x-model="form.session_id">
                        <option value="">- Aucune -</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}">{{ $s->atelier?->title ? $s->atelier->title.' · ' : '' }}{{ $s->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ukc-form-grid">
                    <div class="ukc-form-row"><label>Jour</label><input type="date" name="slDay" x-model="form.day"></div>
                    <div class="ukc-form-row"><label>Heure début</label><input type="time" name="slStart" x-model="form.start"></div>
                </div>
                <div class="ukc-form-row"><label>Salle</label><input type="text" name="slRoom" x-model="form.room" list="roomsList"></div>
                <div class="ukc-form-row">
                    <label>Communication (optionnel)</label>
                    <select name="submission_id" x-model="form.submission_id">
                        <option value="">- Créneau libre -</option>
                        @foreach ($accepted as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->ref_code }} - {{ \Illuminate\Support\Str::limit($sub->title, 45) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ukc-form-row"><label>Intitulé libre (si pas de communication)</label><input type="text" name="slTitle" x-model="form.title" placeholder="Ex. Pause-café"></div>
                <div class="box-foot">
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
                    <button type="submit" class="ukc-btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modale « Associer une salle » à un atelier ===== --}}
<div class="ukc-modal" x-show="modal==='room'" x-cloak @click.self="modal=null" style="display:none;">
    <div class="box">
        <div class="box-head">
            <span x-text="'Salle - ' + (form.title || 'atelier')"></span>
            <button type="button" @click="modal=null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="box-body">
            <form method="POST" :action="'{{ url('admin/programme/ateliers') }}/'+form.id+'/salle'">
                @csrf
                <input type="hidden" name="edition_id" value="{{ $edition_id }}">
                <div class="ukc-form-row">
                    <label>Salle associée</label>
                    <select name="atelierRoom" x-model="form.room">
                        <option value="">- Aucune (salle à définir) -</option>
                        @foreach ($spaces as $space)
                            <option value="{{ $space['name'] }}">{{ $space['name'] }}@if (! empty($space['capacity'])) · cap. {{ $space['capacity'] }}@endif</option>
                        @endforeach
                        @foreach ($usedRooms as $r)
                            @unless (collect($spaces)->pluck('name')->contains($r))<option value="{{ $r }}">{{ $r }}</option>@endunless
                        @endforeach
                    </select>
                    <p class="ukc-hint">La salle est appliquée à l’atelier, à ses sessions et à leurs créneaux.</p>
                </div>
                <div class="box-foot">
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
                    <button type="submit" class="ukc-btn">Enregistrer la salle</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modale Espace ===== --}}
<div class="ukc-modal" x-show="modal==='space'" x-cloak @click.self="modal=null" style="display:none;">
    <div class="box">
        <div class="box-head">
            <span>Ajouter un espace</span>
            <button type="button" @click="modal=null" style="font-size:22px;color:var(--ukc-muted);background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="box-body">
            <form method="POST" action="{{ route('admin.program.spaces.store') }}?edition_id={{ $edition_id }}">
                @csrf
                <div class="ukc-form-row"><label>Nom de l’espace</label><input type="text" name="spaceName" x-model="form.name" required placeholder="Ex. Amphithéâtre principal"></div>
                <div class="ukc-form-grid">
                    <div class="ukc-form-row"><label>Capacité</label><input type="number" name="spaceCapacity" x-model="form.capacity" min="0"></div>
                    <div class="ukc-form-row"><label>Bâtiment</label><input type="text" name="spaceBuilding" x-model="form.building"></div>
                </div>
                <div class="ukc-form-row"><label>Note (équipements, usage…)</label><input type="text" name="spaceNote" x-model="form.note"></div>
                <div class="box-foot">
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
                    <button type="submit" class="ukc-btn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
