@php
    // ⚠️ Les espaces SUFFISENT : toute salle employée par le programme y est
    // déclarée d'office (cf. ProgramController::index). Rajouter ici une liste
    // de « salles utilisées » remettrait un nom qu'on ne peut pas renommer.
    $roomOptions = collect($spaces)->pluck('name')->filter()->unique()->values();
@endphp

<datalist id="roomsList">
    @foreach ($roomOptions as $r)<option value="{{ $r }}">@endforeach
</datalist>

{{-- ===== Panneau latéral Atelier (slideover) ===== --}}
<x-ukc.slideover open="modal==='atelier'" onClose="modal=null" width="440px">
    <x-slot:header><span x-text="form.id ? 'Modifier l’atelier' : 'Nouvel atelier'"></span></x-slot:header>

    <form method="POST" id="atelierForm" :action="form.id ? '{{ url('admin/programme/ateliers') }}/'+form.id : '{{ route('admin.program.ateliers.store') }}'">
        @csrf
        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
        <input type="hidden" name="event_id" value="{{ $event_id }}">
        <div class="ukc-form-row">
            <label>Intitulé de l’atelier</label>
            <input type="text" name="atelierTitle" x-model="form.title" required placeholder="Ex. Atelier Formation &amp; pédagogie">
        </div>
        <div class="ukc-form-row">
            <label>Axes thématiques</label>
            @forelse ($tracks as $t)
                <label class="ukc-check"><input type="checkbox" name="atelierTracks[]" value="{{ $t }}" x-model="form.tracks"> {{ $t }}</label>
            @empty
                <p class="ukc-hint">Aucun axe défini pour cet événement (réglés dans le formulaire d’événement).</p>
            @endforelse
        </div>
        <div class="ukc-form-row" x-show="form.id" x-cloak>
            <label>Salle associée (optionnel)</label>
            <input type="text" name="atelierRoom" x-model="form.room" list="roomsList" placeholder="Ex. Salle A">
        </div>
    </form>

    <x-slot:footer>
        <span style="flex:1;"></span>
        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
        <button type="submit" form="atelierForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
    </x-slot:footer>
</x-ukc.slideover>

{{-- ===== Panneau latéral Session (slideover) ===== --}}
<x-ukc.slideover open="modal==='session'" onClose="modal=null" width="460px">
    <x-slot:header><span x-text="form.id ? 'Modifier la session' : 'Nouvelle session'"></span></x-slot:header>

    <form method="POST" id="sessionForm" :action="form.id ? '{{ url('admin/programme/sessions') }}/'+form.id : '{{ route('admin.program.sessions.store') }}'">
        @csrf
        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
        <input type="hidden" name="event_id" value="{{ $event_id }}">
        <div class="ukc-form-row">
            <label>Type</label>
            <select name="sType" x-model="form.type">
                <option value="atelier">Session d’atelier</option>
                <option value="plenary">Plénière</option>
            </select>
        </div>
        <div class="ukc-form-row" x-show="form.type==='atelier'" x-cloak>
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
            <div class="ukc-form-row"><label>Salle</label><input type="text" name="sRoom" x-model="form.room" list="roomsList" placeholder="Ex. Salle 1"></div>
        </div>
        <div class="ukc-form-grid">
            <div class="ukc-form-row"><label>Début</label><input type="time" name="sStart" x-model="form.start"></div>
            <div class="ukc-form-row"><label>Fin</label><input type="time" name="sEnd" x-model="form.end"></div>
        </div>
        {{-- ⚠️ Modérateur / rapporteur sont rattachés à un COMPTE par e-mail (c'est
             ce qui ouvre leurs espaces /moderation et /rapports). D'où une
             auto-complétion : on choisit une personne existante, et son e-mail (le
             lien) part dans un champ caché. Taper un nom à la main ne lie personne. --}}
        <div class="ukc-form-grid">
            @foreach (['moderator' => 'Modérateur', 'rapporteur' => 'Rapporteur'] as $field => $labelRole)
                <div class="ukc-form-row">
                    <label>{{ $labelRole }}</label>
                    <div class="ukc-combo" style="position:relative;" @click.outside="if (peopleField==='{{ $field }}') peopleField=null">
                        <input type="text" name="s{{ ucfirst($field) }}" x-model="form.{{ $field }}" autocomplete="off"
                               placeholder="Nom ou e-mail du compte"
                               @focus="searchPeople('{{ $field }}')"
                               @input.debounce.250ms="onPersonInput('{{ $field }}')">
                        <input type="hidden" name="s{{ ucfirst($field) }}Email" x-model="form.{{ $field }}_email">
                        <ul class="ukc-combo-menu" x-show="peopleField==='{{ $field }}' && peopleResults.length" x-cloak>
                            <template x-for="p in peopleResults" :key="p.email">
                                <li @mousedown.prevent="pickPerson('{{ $field }}', p)">
                                    <strong x-text="p.name"></strong>
                                    <span x-text="p.email"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="ukc-combo-hint" x-cloak>
                        <span x-show="form.{{ $field }}_email" style="color:var(--ukc-green);">
                            <x-ukc.icon name="check" :size="12" /> Lié à <span x-text="form.{{ $field }}_email"></span>
                        </span>
                        <span x-show="form.{{ $field }} && !form.{{ $field }}_email" style="color:#b45309;">
                            Choisissez une personne dans la liste pour la lier à son compte.
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </form>

    <x-slot:footer>
        <span style="flex:1;"></span>
        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
        <button type="submit" form="sessionForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
    </x-slot:footer>
</x-ukc.slideover>

{{-- ===== Panneau latéral Créneau (slideover) ===== --}}
{{-- Un créneau se range DANS une séance : il en prend le jour, la salle et une
     heure de sa fenêtre, et n'accueille que des communications encore à placer
     dans les axes de cette séance. Règles dans `resources/js/program-pure.js`.
     ⚠️ Aide à la saisie seulement - c'est `ProgramController::slotFrame()` qui
     décide, un champ en lecture seule se retirant en deux clics. --}}
<div x-data="ukcSlotForm(@js($slotMeta))">
<x-ukc.slideover open="modal==='slot'" onClose="modal=null" width="460px">
    <x-slot:header><span x-text="form.id ? 'Modifier le créneau' : 'Nouveau créneau'"></span></x-slot:header>

    <form method="POST" id="slotForm" :action="form.id ? '{{ url('admin/programme/creneaux') }}/'+form.id : '{{ route('admin.program.slots.store') }}'">
        @csrf
        <template x-if="form.id"><input type="hidden" name="_method" value="PUT"></template>
        <input type="hidden" name="event_id" value="{{ $event_id }}">
        <div class="ukc-form-row">
            <label>Session</label>
            <select name="slSessionId" x-model="form.session_id" @change="appliqueSeance(form)">
                <option value="">- Aucune -</option>
                @foreach ($sessions as $s)
                    <option value="{{ $s->id }}">{{ $s->atelier?->title ? $s->atelier->title.' - ' : '' }}{{ $s->label }}</option>
                @endforeach
            </select>
        </div>
        <div class="ukc-form-grid">
            <div class="ukc-form-row">
                <label>Jour</label>
                <input type="date" name="slDay" x-model="form.day"
                       :readonly="!! (slotSeance(form.session_id) || {}).day">
            </div>
            <div class="ukc-form-row">
                <label>Heure début</label>
                <input type="time" name="slStart" x-model="form.start"
                       :min="slotFenetre(form.session_id).min" :max="slotFenetre(form.session_id).max">
            </div>
        </div>
        <div class="ukc-form-row">
            <label>Salle</label>
            <input type="text" name="slRoom" x-model="form.room" list="roomsList"
                   :readonly="!! (slotSeance(form.session_id) || {}).room">
        </div>
        <div class="ukc-form-row">
            <label>Communication (optionnel)</label>
            <select name="submission_id" x-model="form.submission_id">
                <option value="">- Créneau libre -</option>
                <template x-for="c in slotChoix(form.session_id, form.submission_id)" :key="c.id">
                    <option :value="c.id" x-text="c.label + (c.horsAxe ? ' (hors axe)' : '')"></option>
                </template>
            </select>
        </div>
        <div class="ukc-form-row"><label>Intitulé libre (si pas de communication)</label><input type="text" name="slTitle" x-model="form.title" placeholder="Ex. Pause-café"></div>
    </form>

    <x-slot:footer>
        <span style="flex:1;"></span>
        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
        <button type="submit" form="slotForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
    </x-slot:footer>
</x-ukc.slideover>
</div>

{{-- ===== Panneau latéral « Associer une salle » à un atelier (slideover) ===== --}}
<x-ukc.slideover open="modal==='room'" onClose="modal=null" width="420px">
    <x-slot:header><span x-text="'Salle - ' + (form.title || 'atelier')"></span></x-slot:header>

    <form method="POST" id="roomForm" :action="'{{ url('admin/programme/ateliers') }}/'+form.id+'/salle'">
        @csrf
        <input type="hidden" name="event_id" value="{{ $event_id }}">
        <div class="ukc-form-row">
            <label>Salle associée</label>
            <select name="atelierRoom" x-model="form.room">
                <option value="">- Aucune (salle à définir) -</option>
                @foreach ($spaces as $space)
                    <option value="{{ $space['name'] }}">{{ $space['name'] }}@if (! empty($space['capacity'])) - cap. {{ $space['capacity'] }}@endif</option>
                @endforeach
            </select>
            <p class="ukc-hint">La salle est appliquée à l’atelier, à ses sessions et à leurs créneaux.</p>
        </div>
    </form>

    <x-slot:footer>
        <span style="flex:1;"></span>
        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
        <button type="submit" form="roomForm" class="ukc-btn ukc-btn--blue">Enregistrer la salle</button>
    </x-slot:footer>
</x-ukc.slideover>

{{-- ===== Panneau latéral Espace (slideover) ===== --}}
<x-ukc.slideover open="modal==='space'" onClose="modal=null" width="420px">
    <x-slot:header><span x-text="form.i !== '' ? 'Modifier l’espace' : 'Ajouter un espace'"></span></x-slot:header>

    {{-- ⚠️ `form.i !== ''` et non `form.i` : l'index du premier espace vaut 0. --}}
    <form method="POST" id="spaceForm"
          :action="(form.i !== '' ? '{{ url('admin/programme/espaces') }}/'+form.i : '{{ route('admin.program.spaces.store') }}')+'?event_id={{ $event_id }}'">
        @csrf
        <template x-if="form.i !== ''"><input type="hidden" name="_method" value="PUT"></template>
        <p class="ukc-hint" x-show="form.i !== ''" x-cloak>
            Renommer ce lieu le renomme aussi sur les séances et les créneaux qui s’y tiennent.
        </p>
        <div class="ukc-form-row"><label>Nom de l’espace</label><input type="text" name="spaceName" x-model="form.name" required placeholder="Ex. Amphithéâtre principal"></div>
        <div class="ukc-form-grid">
            <div class="ukc-form-row"><label>Capacité</label><input type="number" name="spaceCapacity" x-model="form.capacity" min="0"></div>
            <div class="ukc-form-row"><label>Bâtiment</label><input type="text" name="spaceBuilding" x-model="form.building"></div>
        </div>
        <div class="ukc-form-row"><label>Note (équipements, usage…)</label><input type="text" name="spaceNote" x-model="form.note"></div>
    </form>

    <x-slot:footer>
        <span style="flex:1;"></span>
        <button type="button" class="ukc-btn ukc-btn--ghost" @click="modal=null">Annuler</button>
        <button type="submit" form="spaceForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
    </x-slot:footer>
</x-ukc.slideover>
