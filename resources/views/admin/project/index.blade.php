@php
    $projectConfig = [
        'tasks' => $tasksPayload,
        'milestones' => $milestonesPayload,
        'statuses' => $statuses,
        'routes' => [
            'taskStore' => route('admin.project.tasks.store', ['edition_id' => $edition?->id]),
            'taskUpdate' => route('admin.project.tasks.update', ':id'),
            'taskDelete' => route('admin.project.tasks.destroy', ':id'),
            'taskMove' => route('admin.project.tasks.move', ':id'),
            'taskComments' => route('admin.project.tasks.comments', ':id'),
            'taskComment' => route('admin.project.tasks.comment', ':id'),
            'taskReschedule' => route('admin.project.tasks.reschedule', ':id'),
            'msStore' => route('admin.project.milestones.store', ['edition_id' => $edition?->id]),
            'msUpdate' => route('admin.project.milestones.update', ':id'),
            'msToggle' => route('admin.project.milestones.toggle', ':id'),
            'msDelete' => route('admin.project.milestones.destroy', ':id'),
            'msReschedule' => route('admin.project.milestones.reschedule', ':id'),
            'seed' => route('admin.project.seed', ['edition_id' => $edition?->id]),
        ],
    ];
    $phases = ['Cadrage', 'Appel à communications', 'Soumissions & relecture', 'Programme', 'Logistique', 'Tenue', 'Après-colloque'];
    $prioClass = "{'ukc-pill--muted':t.priority==='low','ukc-pill--info':t.priority==='normal','ukc-pill--warn':t.priority==='high','ukc-pill--err':t.priority==='urgent'}";
@endphp

<x-layouts.admin title="Projet" :subtitle="$edition ? (($edition->title ?: $edition->year).' · piloté comme un projet') : 'Pilotez chaque édition comme un projet'">
    @if ($edition)
        <x-slot:actions>
            <a href="{{ route('admin.project.export', ['edition_id' => $edition->id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="sheet" :size="16" /> Export Excel</a>
            <a href="{{ route('admin.project.report', ['edition_id' => $edition->id]) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="chart" :size="16" /> Rapport</a>
        </x-slot:actions>
    @endif

    <div class="admin-page admin-project" x-data="ukcProject({{ \Illuminate\Support\Js::from($projectConfig) }})">

        {{-- Notification AJAX (sans rechargement) --}}
        <div class="ukc-flash" x-show="flash" x-cloak x-transition
             :class="flashType==='err' ? 'ukc-flash--err' : 'ukc-flash--ok'" x-text="flash"></div>

        {{-- Sélecteur d'édition (change = navigation) --}}
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
        @else
            {{-- Projet vierge --}}
            <div class="ukc-empty" style="text-align:center;padding:40px 24px;" x-show="isEmpty()" x-cloak>
                <x-ukc.icon name="layers" :size="34" />
                <h3 style="margin-top:10px;">Ce projet est vierge</h3>
                <p>Démarrez avec le cycle de vie type d’un colloque (jalons + tâches modèles), pré-rempli d’après les dates clés de l’édition — vous ajusterez ensuite.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:14px;">
                    <button type="button" class="ukc-btn ukc-btn--blue" :disabled="saving" @click="seed()"><x-ukc.icon name="sparkles" :size="16" /> Initialiser le projet</button>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="openNewTask()"><x-ukc.icon name="plus" :size="16" /> Créer une tâche vide</button>
                </div>
            </div>

            {{-- Projet peuplé --}}
            <div x-show="!isEmpty()" x-cloak>
                {{-- Indicateurs (recalculés en direct) --}}
                <div class="ukc-stats">
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="chart" :size="20" /></div><div class="lbl">Avancement</div><div class="val blue"><span x-text="st.progress"></span>%</div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--amber"><x-ukc.icon name="inbox" :size="20" /></div><div class="lbl">Tâches ouvertes</div><div class="val warn" x-text="st.open"></div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="check-circle" :size="20" /></div><div class="lbl">Terminées</div><div class="val green" x-text="st.done"></div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--red"><x-ukc.icon name="clock" :size="20" /></div><div class="lbl">En retard</div><div class="val" style="color:#dc2626;" x-text="st.overdue"></div></div>
                    <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="flag" :size="20" /></div><div class="lbl">Jalons</div><div class="val"><span x-text="st.msDone"></span>/<span x-text="st.msTotal"></span></div></div>
                </div>

                {{-- Barre de progression --}}
                <div class="ukc-card" style="margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:13px;">
                        <strong>Avancement global</strong>
                        <span style="color:var(--ukc-muted);"><span x-text="st.done"></span> / <span x-text="st.total"></span> tâches</span>
                    </div>
                    <div class="ukc-progress"><div class="ukc-progress__bar" :style="'width:'+st.progress+'%'"></div></div>
                    <div style="margin-top:10px;font-size:12.5px;color:var(--ukc-muted);" x-show="st.next">
                        <x-ukc.icon name="flag" :size="13" /> Prochain jalon : <strong x-text="st.next?.title"></strong><template x-if="st.next?.dueLabel"><span> — <span x-text="st.next.dueLabel"></span></span></template>
                    </div>
                </div>

                {{-- Sous-navigation + actions --}}
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <div class="ukc-nav-docs">
                        <button type="button" :class="{ active: view==='board' }" @click="view='board'">Tableau</button>
                        <button type="button" :class="{ active: view==='list' }" @click="view='list'">Liste</button>
                        <button type="button" :class="{ active: view==='milestones' }" @click="view='milestones'">Jalons</button>
                        <button type="button" :class="{ active: view==='calendar' }" @click="openCalendar()">Calendrier</button>
                        <button type="button" :class="{ active: view==='charts' }" @click="openCharts()">Diagrammes</button>
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
                                <template x-for="t in colTasks('{{ $key }}')" :key="t.id">
                                    <div class="ukc-kanban__card" draggable="true"
                                         @dragstart="onDragStart(t.id, '{{ $key }}')"
                                         :class="{ 'is-overdue': t.overdue }"
                                         @click="openEditTask(t)">
                                        <div class="ukc-kanban__card-title" x-text="t.title"></div>
                                        <div class="ukc-kanban__card-meta">
                                            <span class="ukc-pill" :class="{{ $prioClass }}" x-text="t.priorityLabel"></span>
                                            <span class="ukc-pill ukc-pill--muted" x-show="t.phase" x-text="t.phase"></span>
                                            <a class="ukc-task-link" x-show="t.linkUrl" :href="t.linkUrl" @click.stop title="Ouvrir la page liée"><x-ukc.icon name="external" :size="12" /> <span x-text="t.linkLabel"></span></a>
                                        </div>
                                        <div class="ukc-kanban__card-foot">
                                            <span x-show="t.assignee" x-text="t.assignee" class="who"></span>
                                            <span class="ukc-kanban__card-badges">
                                                <span x-show="t.checkTotal > 0" class="chk" :class="{ 'chk--full': t.checkDone === t.checkTotal }"><x-ukc.icon name="check-circle" :size="12" /> <span x-text="t.checkDone + '/' + t.checkTotal"></span></span>
                                                <span x-show="t.dueLabel" :class="{ 'due-late': t.overdue }"><x-ukc.icon name="clock" :size="12" /> <span x-text="t.dueLabel"></span></span>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="ukc-kanban__add" @click="openNewTask('{{ $key }}')">+ Ajouter</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ===== LISTE ===== --}}
                <div class="ukc-card" style="padding:0;" x-show="view==='list'">
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Tâche</th><th>Phase</th><th>Responsable</th><th>Priorité</th><th>Échéance</th><th>Statut</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                                <template x-for="t in tasks" :key="t.id">
                                    <tr :class="{ 'is-overdue-row': t.overdue }">
                                        <td><strong x-text="t.title"></strong></td>
                                        <td><span class="ukc-pill ukc-pill--muted" x-show="t.phase" x-text="t.phase"></span><span x-show="!t.phase">—</span></td>
                                        <td x-text="t.assignee || '—'"></td>
                                        <td><span class="ukc-pill" :class="{{ $prioClass }}" x-text="t.priorityLabel"></span></td>
                                        <td style="white-space:nowrap;"><span x-show="t.dueLabel" :class="{ 'due-late': t.overdue }" x-text="t.dueLabel"></span><span x-show="!t.dueLabel">—</span></td>
                                        <td x-text="statuses[t.status]"></td>
                                        <td>
                                            <div class="ukc-actions" style="justify-content:flex-end;">
                                                <a x-show="t.linkUrl" :href="t.linkUrl" class="ukc-btn--icon" title="Ouvrir la page liée"><x-ukc.icon name="external" :size="16" /></a>
                                                <button type="button" class="ukc-btn--icon" title="Modifier" @click="openEditTask(t)"><x-ukc.icon name="pen" :size="16" /></button>
                                                <button type="button" class="ukc-btn--icon" title="Supprimer" @click="deleteTask(t)"><x-ukc.icon name="trash" :size="16" /></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="tasks.length === 0"><td colspan="7" style="padding:24px;text-align:center;color:var(--ukc-muted);">Aucune tâche.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ===== JALONS ===== --}}
                <div class="ukc-card" x-show="view==='milestones'">
                    <template x-for="m in milestones" :key="m.id">
                        <div class="ukc-milestone" :class="{ 'is-overdue': m.overdue }">
                            <button type="button" class="ukc-milestone__check" :class="{ 'is-done': m.done }" @click="toggleMs(m)" :title="m.done ? 'Rouvrir' : 'Marquer atteint'">
                                <template x-if="m.done"><x-ukc.icon name="check" :size="14" /></template>
                            </button>
                            <div class="ukc-milestone__body">
                                <strong :class="{ done: m.done }" x-text="m.title"></strong>
                                <div class="sub" x-show="m.description" x-text="m.description"></div>
                            </div>
                            <div class="ukc-milestone__date">
                                <span x-show="m.dueLabel" :class="{ 'due-late': m.overdue }" x-text="m.dueLabel"></span>
                                <span x-show="!m.dueLabel" style="color:var(--ukc-light);">Sans date</span>
                            </div>
                            <div class="ukc-actions">
                                <button type="button" class="ukc-btn--icon" title="Modifier" @click="openEditMs(m)"><x-ukc.icon name="pen" :size="16" /></button>
                                <button type="button" class="ukc-btn--icon" title="Supprimer" @click="deleteMs(m)"><x-ukc.icon name="trash" :size="16" /></button>
                            </div>
                        </div>
                    </template>
                    <p x-show="milestones.length === 0" style="color:var(--ukc-muted);text-align:center;padding:20px;">Aucun jalon.</p>
                </div>

                {{-- ===== CALENDRIER DES ÉCHÉANCES (rendu au chargement) ===== --}}
                <div class="ukc-card" x-show="view==='calendar'">
                    <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--ukc-muted);margin-bottom:10px;">
                        <span><span class="ukc-cal-dot" style="background:#7c3aed;"></span> Jalon (◆)</span>
                        <span><span class="ukc-cal-dot" style="background:#223ea4;"></span> Tâche</span>
                        <span><span class="ukc-cal-dot" style="background:#16a34a;"></span> Terminé / atteint</span>
                        <span><span class="ukc-cal-dot" style="background:#dc2626;"></span> En retard</span>
                    </div>
                    <div id="projectCalendar"></div>
                    <p style="font-size:12px;color:var(--ukc-muted);margin-top:8px;">Astuce : faites glisser un événement pour changer son échéance.</p>
                </div>

                {{-- ===== DIAGRAMMES ===== --}}
                <div x-show="view==='charts'" x-cloak>
                    <div class="ukc-charts-grid">
                        <div class="ukc-card">
                            <h3 style="margin:0 0 10px;font-size:14px;">Tâches par statut</h3>
                            <div style="position:relative;height:260px;"><canvas id="projChartStatus"></canvas></div>
                        </div>
                        <div class="ukc-card">
                            <h3 style="margin:0 0 10px;font-size:14px;">Avancement par phase</h3>
                            <div style="position:relative;height:260px;"><canvas id="projChartPhase"></canvas></div>
                        </div>
                        <div class="ukc-card" style="grid-column:1 / -1;">
                            <h3 style="margin:0 0 10px;font-size:14px;">Charge par responsable</h3>
                            <div style="position:relative;height:280px;"><canvas id="projChartAssignee"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Panneau latéral : tâche ===== --}}
            <x-ukc.slideover open="taskOpen" onClose="taskOpen = false" width="460px">
                <x-slot:header><span x-text="task.id ? 'Modifier la tâche' : 'Nouvelle tâche'"></span></x-slot:header>
                <form @submit.prevent="saveTask()" id="taskForm">
                    <div class="ukc-form-row"><label>Intitulé</label><input type="text" x-model="task.title" required maxlength="200"></div>
                    <div class="ukc-form-row"><label>Description</label><textarea rows="3" x-model="task.description"></textarea></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="ukc-form-row"><label>Colonne</label>
                            <select x-model="task.status">@foreach ($statuses as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        </div>
                        <div class="ukc-form-row"><label>Priorité</label>
                            <select x-model="task.priority">@foreach ($priorities as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="ukc-form-row"><label>Responsable</label>
                            <select x-model="task.assignee_id">
                                <option value="">— Aucun —</option>
                                @foreach ($team as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="ukc-form-row"><label>Échéance</label><input type="date" x-model="task.due_date"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="ukc-form-row"><label>Phase</label>
                            <input type="text" x-model="task.phase" list="projectPhases" placeholder="Ex. Programme">
                            <datalist id="projectPhases">@foreach ($phases as $p)<option value="{{ $p }}">@endforeach</datalist>
                        </div>
                        <div class="ukc-form-row"><label>Lien contextuel</label>
                            <select x-model="task.link_key">
                                <option value="">— Aucun —</option>
                                @foreach ($linkTargets as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Checklist --}}
                    <div class="ukc-form-row">
                        <label>Checklist <span x-show="checklistProgress().total" style="color:var(--ukc-muted);font-weight:500;" x-text="'· ' + checklistProgress().done + '/' + checklistProgress().total"></span></label>
                        <div class="ukc-checklist">
                            <template x-for="(item, i) in task.checklist" :key="i">
                                <div class="ukc-checklist__item">
                                    <input type="checkbox" x-model="item.done">
                                    <span class="txt" :class="{ done: item.done }" x-text="item.text"></span>
                                    <button type="button" class="ukc-btn--icon" @click="removeCheck(i)" title="Retirer">&times;</button>
                                </div>
                            </template>
                            <div class="ukc-checklist__add">
                                <input type="text" x-model="newCheck" placeholder="Ajouter un élément…" @keydown.enter.prevent="addCheck()">
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="addCheck()">Ajouter</button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Commentaires (tâche existante) --}}
                <div class="ukc-form-row" x-show="task.id" style="margin-top:4px;">
                    <label>Commentaires</label>
                    <div class="ukc-comment-add">
                        <textarea x-model="newComment" rows="2" placeholder="Écrire un commentaire…" @keydown.enter.meta="submitComment()"></textarea>
                        <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" :disabled="commentBusy" @click="submitComment()">Commenter</button>
                    </div>
                    <div class="ukc-comments">
                        <template x-for="c in task.comments" :key="c.id">
                            <div class="ukc-comment">
                                <div class="ukc-comment__head"><strong x-text="c.author"></strong><span x-text="c.at"></span></div>
                                <div class="ukc-comment__body" x-text="c.body"></div>
                            </div>
                        </template>
                        <p x-show="task.comments.length === 0" style="color:var(--ukc-muted);font-size:12.5px;">Aucun commentaire pour l’instant.</p>
                    </div>
                </div>
                <x-slot:footer>
                    <button type="button" class="ukc-btn ukc-btn--ghost" x-show="task.id" @click="deleteTask(task)"><x-ukc.icon name="trash" :size="15" /> Supprimer</button>
                    <span style="flex:1;"></span>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="taskOpen = false">Annuler</button>
                    <button type="submit" form="taskForm" class="ukc-btn ukc-btn--blue" :disabled="saving">Enregistrer</button>
                </x-slot:footer>
            </x-ukc.slideover>

            {{-- ===== Panneau latéral : jalon ===== --}}
            <x-ukc.slideover open="msOpen" onClose="msOpen = false" width="420px">
                <x-slot:header><span x-text="ms.id ? 'Modifier le jalon' : 'Nouveau jalon'"></span></x-slot:header>
                <form @submit.prevent="saveMs()" id="msForm">
                    <div class="ukc-form-row"><label>Intitulé</label><input type="text" x-model="ms.title" required maxlength="200"></div>
                    <div class="ukc-form-row"><label>Description</label><textarea rows="2" x-model="ms.description"></textarea></div>
                    <div class="ukc-form-row"><label>Échéance</label><input type="date" x-model="ms.due_date"></div>
                </form>
                <x-slot:footer>
                    <button type="button" class="ukc-btn ukc-btn--ghost" @click="msOpen = false">Annuler</button>
                    <button type="submit" form="msForm" class="ukc-btn ukc-btn--blue" :disabled="msSaving">Enregistrer</button>
                </x-slot:footer>
            </x-ukc.slideover>
        @endif
    </div>
</x-layouts.admin>
