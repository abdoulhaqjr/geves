<x-layouts.admin title="Options" subtitle="Règles de l’événement : dépôt, attribution, évaluation">
    <div class="admin-page admin-submissions">

        <div class="page-head" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
            <div class="ukc-field" style="min-width:320px;max-width:460px;margin:0;">
                <label for="event_id">Événement</label>
                <select id="event_id" name="event_id" onchange="window.location = '{{ route('admin.submissions.options') }}?event_id=' + this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($events as $ed)<option value="{{ $ed->id }}" @selected((string) $ed->id === $event_id)>{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
        </div>

        @include('admin.submissions._nav', ['current' => 'options'])

        @if (! $event)
            <div class="ukc-card"><p style="margin:0;color:var(--ukc-muted);">Sélectionnez une événement pour régler ses règles d’évaluation.</p></div>
        @else
            {{-- Dépôt : limites du formulaire public. Elles étaient dans un onglet
                 de l'éditeur de formulaire ; ce sont des règles, elles rejoignent
                 les autres. L'éditeur ne garde que la composition des champs. --}}
            <form method="POST" action="{{ route('admin.evenements.form.rules', $event) }}" style="margin-bottom:18px;">
                @csrf
                @method('PUT')
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="list" :size="18" /> Dépôt : règles &amp; limites</span></h2>
                    <p class="desc">
                        Appliquées au <a href="{{ route('admin.evenements.form', $event) }}"
                        style="color:var(--ukc-blue);font-weight:600;">formulaire de soumission</a> de cette événement.
                    </p>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                        <div class="ukc-field">
                            <label>Titre - minimum de caractères <span class="hint">(0 = aucun)</span></label>
                            <input type="number" name="rules[title_min]" value="{{ $formRules['title_min'] ?? 0 }}" min="0" max="1000" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Titre - maximum de caractères</label>
                            <input type="number" name="rules[title_max]" value="{{ $formRules['title_max'] ?? 256 }}" min="1" max="1000" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Résumé - minimum de mots</label>
                            <input type="number" name="rules[word_min]" value="{{ $formRules['word_min'] }}" min="0" max="5000" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Résumé - maximum de mots</label>
                            <input type="number" name="rules[word_limit]" value="{{ $formRules['word_limit'] }}" min="1" max="5000" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Mots-clés - minimum</label>
                            <input type="number" name="rules[keywords_min]" value="{{ $formRules['keywords_min'] }}" min="0" max="50" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Mots-clés - maximum</label>
                            <input type="number" name="rules[keywords_max]" value="{{ $formRules['keywords_max'] }}" min="1" max="50" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Nombre max d’auteurs</label>
                            <input type="number" name="rules[max_authors]" value="{{ $formRules['max_authors'] }}" min="1" max="30" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Taille max fichier (Mo)</label>
                            <input type="number" name="rules[file_max_mb]" value="{{ $formRules['file_max_mb'] }}" min="1" max="50" @disabled(! $canWrite)>
                        </div>
                        <div class="ukc-field">
                            <label>Formats acceptés</label>
                            <input type="text" name="rules[file_formats]" value="{{ $formRules['file_formats'] }}" placeholder="pdf, docx" @disabled(! $canWrite)>
                        </div>
                    </div>

                    <div class="ukc-opts-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:4px 24px;margin-top:14px;">
                        <label class="ukc-check"><input type="checkbox" name="rules[require_account]" value="1" @checked($formRules['require_account']) @disabled(! $canWrite)> Compte participant requis pour soumettre</label>
                        <label class="ukc-check"><input type="checkbox" name="rules[no_duplicate]" value="1" @checked($formRules['no_duplicate']) @disabled(! $canWrite)> Un seul dépôt par titre (anti-doublon)</label>
                        <label class="ukc-check"><input type="checkbox" name="rules[live_counter]" value="1" @checked($formRules['live_counter']) @disabled(! $canWrite)> Afficher le compteur de mots en direct</label>
                        <label class="ukc-check"><input type="checkbox" name="rules[captcha]" value="1" @checked($formRules['captcha']) @disabled(! $canWrite)> Captcha sur le formulaire public</label>
                    </div>

                    @if ($canWrite)
                        <div class="ukc-form-actions" style="margin-top:16px;text-align:right;">
                            <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les règles de dépôt</button>
                        </div>
                    @endif
                </div>
            </form>
            {{-- Un seul formulaire : les quatre règles se lisent et s'enregistrent
                 ensemble (elles étaient éclatées entre paramètres d'URL de la page
                 d'attribution et fiche d'événement). --}}
            <form method="POST" action="{{ route('admin.evenements.reviewers.criteria', $event) }}"
                  x-data="{ criteria: {{ Illuminate\Support\Js::from(collect($reviewCriteria)->pluck('label')->values()) }} }">
                @csrf

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="refresh" :size="18" /> Attribution des relectures</span></h2>
                    <p class="desc">
                        Règles utilisées par l’<a href="{{ route('admin.submissions.auto-assign', ['event_id' => $event_id]) }}"
                        style="color:var(--ukc-blue);font-weight:600;">attribution automatique</a> pour proposer un plan.
                    </p>
                    <div class="ukc-opts-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                        <div class="ukc-field">
                            <label for="review_target">Relecteurs par soumission</label>
                            <input type="number" id="review_target" name="review_target" min="1" max="5"
                                   value="{{ $target }}" @disabled(! $canWrite)>
                            <span class="hint">Nombre de relectures visé pour chaque communication (1 à 5).</span>
                        </div>
                        <div class="ukc-field">
                            <label for="review_max_load">Charge maximale par relecteur</label>
                            <input type="number" id="review_max_load" name="review_max_load" min="0" max="50"
                                   value="{{ $maxLoad }}" @disabled(! $canWrite)>
                            <span class="hint">0 = illimitée. Au-delà, le moteur ne propose plus ce relecteur.</span>
                        </div>
                    </div>
                </div>

                <div class="ukc-card" style="margin-top:18px;">
                    <h2><span><x-ukc.icon name="award" :size="18" /> Grille de notation &amp; délai</span></h2>
                    <p class="desc">
                        Critères notés de 0 à {{ $reviewScale }} par les relecteurs ; la note globale /20 en est
                        dérivée (moyenne). Laissez la grille par défaut ou personnalisez-la pour cette événement.
                    </p>

                    <div class="ukc-field" style="max-width:320px;margin-bottom:16px;">
                        <label for="review_due_days">Délai d’évaluation (jours)</label>
                        <input type="number" id="review_due_days" name="review_due_days" min="1" max="180"
                               value="{{ $reviewDueDays }}" @disabled(! $canWrite)>
                        <span class="hint">
                            Échéance calculée à l’assignation. Passé ce délai, la relecture est signalée
                            « en retard » au comité et au relecteur.
                        </span>
                    </div>

                    <template x-for="(label, i) in criteria" :key="i">
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                            <span style="color:var(--ukc-muted);font-size:.85rem;width:20px;" x-text="(i+1)+'.'"></span>
                            <input type="text" :name="'criteria['+i+']'" x-model="criteria[i]"
                                   placeholder="Intitulé du critère" style="flex:1;" @disabled(! $canWrite)>
                            @if ($canWrite)
                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                                        @click="criteria.splice(i,1)" title="Retirer">
                                    <x-ukc.icon name="close" :size="14" />
                                </button>
                            @endif
                        </div>
                    </template>
                    @if ($canWrite)
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                            <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm" @click="criteria.push('')">
                                <x-ukc.icon name="plus" :size="14" /> Ajouter un critère
                            </button>
                        </div>
                    @endif
                </div>

                @if ($canWrite)
                    <div class="ukc-form-actions" style="margin-top:16px;text-align:right;">
                        <button type="submit" class="ukc-btn ukc-btn--blue">Enregistrer les règles de relecture</button>
                    </div>
                @else
                    <p class="hint" style="margin-top:10px;font-size:12.5px;color:var(--ukc-muted);">
                        Lecture seule : modifier ces règles demande le droit d’écriture sur l’événement.
                    </p>
                @endif
            </form>
        @endif
    </div>
</x-layouts.admin>
