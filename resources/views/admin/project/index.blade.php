@php
    // Cartes kanban hydratées côté client (Alpine) — un objet léger par tâche.
    $cardOf = fn ($t) => [
        'id' => $t->id,
        'title' => $t->title,
        'description' => (string) $t->description,
        'status' => $t->status,
        'priority' => $t->priority,
        'priorityLabel' => $t->priorityLabel(),
        'phase' => (string) $t->phase,
        'assignee_id' => $t->assignee_id,
        'assignee' => $t->assignee?->name,
        'due_date' => $t->due_date?->toDateString(),
        'dueLabel' => $t->due_date?->format('d/m/Y'),
        'overdue' => $t->isOverdue(),
    ];
    $cols = [];
    foreach ($columns as $status => $list) {
        $cols[$status] = $list->map($cardOf)->values();
    }
    $projectConfig = [
        'cols' => $cols,
        'moveUrl' => route('admin.project.tasks.move', ['task' => ':id', 'edition_id' => $edition?->id]),
        'routes' => [
            'taskStore' => route('admin.project.tasks.store'),
            'taskUpdate' => route('admin.project.tasks.update', ':id'),
            'msStore' => route('admin.project.milestones.store'),
            'msUpdate' => route('admin.project.milestones.update', ':id'),
        ],
    ];
    $phases = ['Cadrage', 'Appel à communications', 'Soumissions & relecture', 'Programme', 'Logistique', 'Tenue', 'Après-colloque'];
    $pill = ['low' => 'muted', 'normal' => 'info', 'high' => 'warn', 'urgent' => 'err'];
@endphp

<x-layouts.admin title="Projet" :subtitle="$edition ? (($edition->title ?: $edition->year).' · piloté comme un projet') : 'Pilotez chaque édition comme un projet'">
    <div class="admin-page admin-project" x-data="ukcProject({{ \Illuminate\Support\Js::from($projectConfig) }})">

        {{-- Sélecteur d'édition --}}
        <div style="max-width:340px;margin-bottom:16px;">
            <div class="ukc-field" style="margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.project.index') }}?edition_id='+this.value">
                    <option value="">— Choisir —</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @if (! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour piloter son projet.</p></div>
        @elseif ($isEmpty)
            <div class="ukc-empty" style="text-align:center;padding:40px 24px;">
                <x-ukc.icon name="layers" :size="34" />
                <h3 style="margin-top:10px;">Ce projet est vierge</h3>
                <p>Démarrez avec le cycle de vie type d’un colloque (jalons + tâches modèles), pré-rempli d’après les dates clés de l’édition — vous ajusterez ensuite.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:14px;">
                    <form method="POST" action="{{ route('admin.project.seed') }}">
                        @csrf<input type="hidden" name="edition_id" value="{{ $edition->id }}">
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="sparkles" :size="16" /> Initialiser le projet</button>
                    </form>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="openNewTask()"><x-ukc.icon name="plus" :size="16" /> Créer une tâche vide</button>
                </div>
            </div>
        @else
            {{-- Indicateurs --}}
            <div class="ukc-stats">
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Avancement</div><div class="val blue">{{ $stats['progress'] }}%</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Tâches ouvertes</div><div class="val warn">{{ $stats['open'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Terminées</div><div class="val green">{{ $stats['done'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En retard</div><div class="val" style="color:#dc2626;">{{ $stats['overdue'] }}</div></div>
                <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="flag" :size="20" /></div><div class="lbl">Jalons</div><div class="val">{{ $stats['milestonesDone'] }}/{{ $stats['milestonesTotal'] }}</div></div>
            </div>

            {{-- Barre de progression --}}
            <div class="ukc-card" style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px;">
                    <strong>Avancement global</strong>
                    <span style="color:var(--ukc-muted);">{{ $stats['done'] }} / {{ $stats['total'] }} tâches</span>
                </div>
                <div class="ukc-progress"><div class="ukc-progress__bar" style="width:{{ $stats['progress'] }}%;"></div></div>
                @if ($stats['nextMilestone'])
                    <div style="margin-top:10px;font-size:12.5px;color:var(--ukc-muted);">
                        <x-ukc.icon name="flag" :size="13" /> Prochain jalon : <strong>{{ $stats['nextMilestone']->title }}</strong>
                        @if ($stats['nextMilestone']->due_date) — {{ $stats['nextMilestone']->due_date->format('d/m/Y') }}@endif
                    </div>
                @endif
            </div>

            {{-- Sous-navigation + actions --}}
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                <div class="ukc-nav-docs">
                    <button type="button" :class="{ active: view==='board' }" @click="view='board'">Tableau</button>
                    <button type="button" :class="{ active: view==='list' }" @click="view='list'">Liste</button>
                    <button type="button" :class="{ active: view==='milestones' }" @click="view='milestones'">Jalons</button>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" x-show="view==='milestones'" @click="openNewMs()"><x-ukc.icon name="plus" :size="14" /> Jalon</button>
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" @click="openNewTask()"><x-ukc.icon name="plus" :size="14" /> Nouvelle tâche</button>
                </div>
            </div>

            {{-- ===== TABLEAU KANBAN ===== --}}
            <div class="ukc-kanban" x-show="view==='board'">
                @foreach ($statuses as $key => $label)
                    <div class="ukc-kanban__col" @dragover.prevent @drop.prevent="onDrop('{{ $key }}')">
                        <div class="ukc-kanban__head">
                            <span>{{ $label }}</span>
                            <span class="ukc-kanban__count" x-text="count('{{ $key }}')"></span>
                        </div>
                        <div class="ukc-kanban__body">
                            <template x-for="t in cols['{{ $key }}']" :key="t.id">
                                <div class="ukc-kanban__card" draggable="true"
                                     @dragstart="onDragStart(t.id, '{{ $key }}')"
                                     :class="{ 'is-overdue': t.overdue }"
                                     @click="openEditTask(t)">
                                    <div class="ukc-kanban__card-title" x-text="t.title"></div>
                                    <div class="ukc-kanban__card-meta">
                                        <span class="ukc-pill" :class="{'ukc-pill--muted':t.priority==='low','ukc-pill--info':t.priority==='normal','ukc-pill--warn':t.priority==='high','ukc-pill--err':t.priority==='urgent'}" x-text="t.priorityLabel"></span>
                                        <span class="ukc-pill ukc-pill--muted" x-show="t.phase" x-text="t.phase"></span>
                                    </div>
                                    <div class="ukc-kanban__card-foot">
                                        <span x-show="t.assignee" x-text="t.assignee" class="who"></span>
                                        <span x-show="t.dueLabel" :class="{ 'due-late': t.overdue }"><x-ukc.icon name="clock" :size="12" /> <span x-text="t.dueLabel"></span></span>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="ukc-kanban__add" @click="openNewTask('{{ $key }}')">+ Ajouter</button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ===== LISTE ===== --}}
            <div class="ukc-card" style="padding:0;" x-show="view==='list'" x-cloak>
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead><tr><th>Tâche</th><th>Phase</th><th>Responsable</th><th>Priorité</th><th>Échéance</th><th>Statut</th><th style="text-align:right;">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($tasks as $t)
                                <tr @class(['is-overdue-row' => $t->isOverdue()])>
                                    <td><strong>{{ $t->title }}</strong></td>
                                    <td>@if ($t->phase)<span class="ukc-pill ukc-pill--muted">{{ $t->phase }}</span>@else — @endif</td>
                                    <td>{{ $t->assignee?->name ?? '—' }}</td>
                                    <td><span class="ukc-pill ukc-pill--{{ $pill[$t->priority] ?? 'muted' }}">{{ $t->priorityLabel() }}</span></td>
                                    <td style="white-space:nowrap;">@if ($t->due_date)<span @class(['due-late' => $t->isOverdue()])>{{ $t->due_date->format('d/m/Y') }}</span>@else — @endif</td>
                                    <td>{{ $statuses[$t->status] ?? $t->status }}</td>
                                    <td>
                                        <div class="ukc-actions" style="justify-content:flex-end;">
                                            <button type="button" class="ukc-btn--icon" title="Modifier" @click="openEditTask({{ \Illuminate\Support\Js::from($cardOf($t)) }})"><x-ukc.icon name="pen" :size="16" /></button>
                                            <form method="POST" action="{{ route('admin.project.tasks.destroy', $t) }}" onsubmit="return confirm('Supprimer cette tâche ?')" style="display:inline;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="ukc-btn--icon" title="Supprimer"><x-ukc.icon name="trash" :size="16" /></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--ukc-muted);">Aucune tâche.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===== JALONS ===== --}}
            <div class="ukc-card" x-show="view==='milestones'" x-cloak>
                @forelse ($milestones as $m)
                    <div class="ukc-milestone @if ($m->isOverdue()) is-overdue @endif">
                        <form method="POST" action="{{ route('admin.project.milestones.toggle', $m) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="ukc-milestone__check @if ($m->isDone()) is-done @endif" title="{{ $m->isDone() ? 'Rouvrir' : 'Marquer atteint' }}">
                                @if ($m->isDone())<x-ukc.icon name="check" :size="14" />@endif
                            </button>
                        </form>
                        <div class="ukc-milestone__body">
                            <strong @class(['done' => $m->isDone()])>{{ $m->title }}</strong>
                            @if ($m->description)<div class="sub">{{ $m->description }}</div>@endif
                        </div>
                        <div class="ukc-milestone__date">
                            @if ($m->due_date)<span @class(['due-late' => $m->isOverdue()])>{{ $m->due_date->format('d/m/Y') }}</span>@else <span style="color:var(--ukc-light);">Sans date</span>@endif
                        </div>
                        <div class="ukc-actions">
                            <button type="button" class="ukc-btn--icon" title="Modifier" @click="openEditMs({{ \Illuminate\Support\Js::from(['id' => $m->id, 'title' => $m->title, 'description' => (string) $m->description, 'due_date' => $m->due_date?->toDateString(), 'status' => $m->status]) }})"><x-ukc.icon name="pen" :size="16" /></button>
                            <form method="POST" action="{{ route('admin.project.milestones.destroy', $m) }}" onsubmit="return confirm('Supprimer ce jalon ?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="ukc-btn--icon" title="Supprimer"><x-ukc.icon name="trash" :size="16" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p style="color:var(--ukc-muted);text-align:center;padding:20px;">Aucun jalon. Ajoutez-en un ou initialisez le projet.</p>
                @endforelse
            </div>

            {{-- ===== Panneau latéral : tâche ===== --}}
            <x-ukc.slideover open="taskOpen" onClose="taskOpen = false" width="460px">
                <x-slot:header><span x-text="task.id ? 'Modifier la tâche' : 'Nouvelle tâche'"></span></x-slot:header>
                <form method="POST" :action="taskAction()" id="taskForm">
                    @csrf
                    <template x-if="task.id"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <div class="ukc-form-row"><label>Intitulé</label><input type="text" name="title" x-model="task.title" required maxlength="200"></div>
                    <div class="ukc-form-row"><label>Description</label><textarea name="description" rows="3" x-model="task.description"></textarea></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="ukc-form-row"><label>Colonne</label>
                            <select name="status" x-model="task.status">
                                @foreach ($statuses as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                        <div class="ukc-form-row"><label>Priorité</label>
                            <select name="priority" x-model="task.priority">
                                @foreach ($priorities as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="ukc-form-row"><label>Responsable</label>
                            <select name="assignee_id" x-model="task.assignee_id">
                                <option value="">— Aucun —</option>
                                @foreach ($team as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="ukc-form-row"><label>Échéance</label><input type="date" name="due_date" x-model="task.due_date"></div>
                    </div>
                    <div class="ukc-form-row"><label>Phase</label>
                        <input type="text" name="phase" x-model="task.phase" list="projectPhases" placeholder="Ex. Programme">
                        <datalist id="projectPhases">@foreach ($phases as $p)<option value="{{ $p }}">@endforeach</datalist>
                    </div>
                </form>
                <x-slot:footer>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="taskOpen = false">Annuler</button>
                    <button type="submit" form="taskForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                </x-slot:footer>
            </x-ukc.slideover>

            {{-- ===== Panneau latéral : jalon ===== --}}
            <x-ukc.slideover open="msOpen" onClose="msOpen = false" width="420px">
                <x-slot:header><span x-text="ms.id ? 'Modifier le jalon' : 'Nouveau jalon'"></span></x-slot:header>
                <form method="POST" :action="msAction()" id="msForm">
                    @csrf
                    <template x-if="ms.id"><input type="hidden" name="_method" value="PUT"></template>
                    <input type="hidden" name="edition_id" value="{{ $edition->id }}">
                    <div class="ukc-form-row"><label>Intitulé</label><input type="text" name="title" x-model="ms.title" required maxlength="200"></div>
                    <div class="ukc-form-row"><label>Description</label><textarea name="description" rows="2" x-model="ms.description"></textarea></div>
                    <div class="ukc-form-row"><label>Échéance</label><input type="date" name="due_date" x-model="ms.due_date"></div>
                </form>
                <x-slot:footer>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="msOpen = false">Annuler</button>
                    <button type="submit" form="msForm" class="ukc-btn ukc-btn--blue">Enregistrer</button>
                </x-slot:footer>
            </x-ukc.slideover>
        @endif
    </div>
</x-layouts.admin>
