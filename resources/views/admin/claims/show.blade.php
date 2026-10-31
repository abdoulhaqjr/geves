<x-layouts.admin :title="$claim->reference" :subtitle="$claim->typeLabel().' - '.$claim->subject">
    <x-slot:actions>
        <a href="{{ route('admin.claims.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="chevron" :size="15" /> Toutes les réclamations</a>
    </x-slot:actions>

    <div class="admin-page">
        <div class="ukc-grid-2" style="align-items:start;">
            {{-- ===== Détail + traitement ===== --}}
            <div style="display:flex;flex-direction:column;gap:18px;">
                <div class="ukc-card">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
                        <span class="ukc-pill ukc-pill--{{ $claim->type === 'incident' ? 'warn' : 'info' }}">{{ $claim->typeLabel() }}</span>
                        <span class="ukc-pill ukc-pill--{{ $claim->statusPill() }}">{{ $claim->statusLabel() }}</span>
                        @if ($claim->category)<span class="ukc-pill ukc-pill--muted">{{ $claim->category }}</span>@endif
                        <span style="font-size:12px;color:var(--ukc-muted);">Source : {{ $claim->source === 'chatbot' ? 'Assistante '.\App\Support\AiAgents::publicName() : 'Saisie back-office' }}</span>
                    </div>
                    <h3 style="margin:0 0 4px;">{{ $claim->subject }}</h3>
                    <div style="font-size:13px;color:var(--ukc-muted);margin-bottom:12px;">
                        {{ $claim->name ?: 'Déclarant inconnu' }} - {{ $claim->email }}
                        @if ($claim->event) - {{ $claim->event->series?->name ? $claim->event->series->name.' - ' : '' }}{{ $claim->event->title ?: $claim->event->year }}@endif
                        - reçue le {{ $claim->created_at?->translatedFormat('d/m/Y à H:i') }}
                    </div>
                    <div class="ukc-rte" style="white-space:pre-line;line-height:1.6;">{{ $claim->message }}</div>

                    @if (filled($claim->resolution))
                        <div style="margin-top:14px;border-top:1px solid var(--ukc-border);padding-top:12px;">
                            <div style="font-size:12px;font-weight:700;color:var(--ukc-muted);margin-bottom:4px;">Résolution</div>
                            <div style="white-space:pre-line;line-height:1.55;">{{ $claim->resolution }}</div>
                        </div>
                    @endif
                </div>

                <div class="ukc-card">
                    <h3>Traitement</h3>
                    <div class="ukc-grid-2" style="align-items:start;gap:16px;margin-top:8px;">
                        <form method="POST" action="{{ route('admin.claims.status', $claim) }}">
                            @csrf
                            <div class="ukc-field-stack">
                            <div class="ukc-field"><label>Statut</label>
                                <select name="status">
                                    @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($claim->status === $k)>{{ $l }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-field"><label>{{ filled($claim->resolution) ? 'Nouvelle réponse au déclarant (facultatif)' : 'Réponse au déclarant (facultatif)' }}</label>
                                {{-- ⚠️⚠️ Le champ part VIDE, même quand une réponse a déjà été
                                     envoyée : celle-ci est affichée juste au-dessus, dans le bloc
                                     « Résolution ». Repeuplé, il montrait deux fois le même texte
                                     et se lisait comme un brouillon qu'on aurait oublié d'effacer.
                                     Vide, il dit ce qu'il est : ce qu'on s'apprête à ENVOYER EN
                                     PLUS. ⚠️ Le laisser vide n'efface rien - `updateStatus()`
                                     n'écrase la résolution que si le champ est rempli. --}}
                                <textarea name="resolution" rows="4" placeholder="{{ filled($claim->resolution) ? 'Une nouvelle réponse remplacera celle ci-dessus et sera envoyée…' : 'Ce qui a été fait / la réponse apportée…' }}">{{ old('resolution') }}</textarea>
                                {{-- ⚠️ Tient sur UNE ligne dans la colonne de gauche (mesuré) : les
                                     deux incises de la version précédente la faisaient déborder. --}}
                                <p style="font-size:12px;color:var(--ukc-muted);margin:6px 0 0;">Une nouvelle réponse ou le statut « Résolue » déclenche un e-mail au déclarant.</p>
                            </div>
                            </div>
                            <div class="ukc-form-actions ukc-form-actions--plain">
                                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Enregistrer</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.claims.assign', $claim) }}">
                            @csrf
                            <div class="ukc-field"><label>Assigné à</label>
                                <select name="assigned_to" onchange="this.form.submit()">
                                    <option value="">- Personne -</option>
                                    @foreach ($assignees as $u)<option value="{{ $u->id }}" @selected($claim->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach
                                </select>
                            </div>
                            <p style="font-size:12px;color:var(--ukc-muted);">Personnes habilitées (admin, gestionnaires ou délégués « réclamations » de cet événement).</p>
                        </form>
                    </div>
                </div>

                @if (auth()->user()->isColloqueAdmin())
                    <div class="ukc-card">
                        <h3>Rattachement</h3>
                        <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">
                            @if ($claim->event)
                                Rattachée à <strong>{{ $claim->event->series?->name ? $claim->event->series->name.' - ' : '' }}{{ $claim->event->title ?: $claim->event->year }}</strong> : ses gestionnaires sont notifiés.
                            @else
                                Réclamation <strong>générale</strong> (sans événement) : les administrateurs sont notifiés. Transférez-la à un événement pour en alerter les gestionnaires.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('admin.claims.transfer', $claim) }}">
                            @csrf
                            <div class="ukc-field"><label>Transférer vers l’événement</label>
                                <select name="event_id">
                                    <option value="">- Choisir un événement -</option>
                                    @foreach ($events as $e)
                                        <option value="{{ $e->id }}" @selected($claim->event_id === $e->id)>{{ $e->series?->name ? $e->series->name.' - ' : '' }}{{ $e->title ?: $e->year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- ⚠️ La conséquence se lit AVANT le geste, pas après : placée sous
                                 le bouton, elle n'était découverte qu'une fois le transfert fait. --}}
                            <p style="font-size:12px;color:var(--ukc-muted);margin:6px 0 0;">Les gestionnaires et co-gestionnaires de l’événement d’arrivée recevront une notification et un e-mail.</p>
                            <div class="ukc-form-actions">
                                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="external" :size="15" /> Transférer</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- ===== Analyse IA ===== --}}
            <div class="ukc-card">
                <h3><x-ukc.icon name="sparkles" :size="18" /> Analyse de traitement (IA)</h3>
                <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">L'IA analyse le souci et propose : une résolution directe, la procédure à suivre, ou explique pourquoi c'est irrésolvable. Elle <strong>propose</strong> - vous décidez.</p>
                <form method="POST" action="{{ route('admin.claims.analyze', $claim) }}">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="sparkles" :size="15" /> {{ $claim->analyses() ? 'Relancer l’analyse' : 'Analyser' }}</button>
                </form>

                @if (session('ukc_error'))
                    <div class="ukc-alert ukc-alert--err" style="margin-top:12px;"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ session('ukc_error') }}</div></div>
                @endif

                {{-- ⚠️ Les analyses sont rangées de la PLUS RÉCENTE à la plus ancienne
                     (`array_unshift` côté contrôleur). Seule la première est dépliée :
                     empilées, dix analyses faisaient une page à faire défiler, et la
                     dernière - la seule qu'on vient lire - se perdait dedans. Les
                     précédentes restent accessibles d'un clic : quelqu'un a pu agir
                     sur une analyse ancienne, et on doit pouvoir la retrouver. --}}
                @php($analyses = $claim->analyses())
                @php($recente = $analyses[0] ?? null)
                @php($anciennes = array_slice($analyses, 1))

                @if ($recente)
                    <div style="margin-top:14px;border-top:1px solid var(--ukc-border);padding-top:12px;">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <x-claims.verdict :verdict="$recente['verdict'] ?? 'procedure'" />
                            <span style="font-size:11.5px;color:var(--ukc-muted);">{{ $recente['at'] ?? '' }} @if (! empty($recente['by']))- {{ $recente['by'] }}@endif</span>
                        </div>
                        @include('admin.claims._analysis', ['a' => $recente, 'claim' => $claim])
                    </div>

                    @if ($anciennes !== [])
                        <div class="ukc-claim-anciennes">
                            <p class="ukc-claim-anciennes__titre">{{ count($anciennes) }} analyse(s) précédente(s)</p>
                            @foreach ($anciennes as $a)
                                <details class="ukc-claim-ancienne">
                                    <summary>
                                        <x-claims.verdict :verdict="$a['verdict'] ?? 'procedure'" />
                                        <span class="quand">{{ $a['at'] ?? '' }} @if (! empty($a['by']))- {{ $a['by'] }}@endif</span>
                                    </summary>
                                    @include('admin.claims._analysis', ['a' => $a, 'claim' => $claim])
                                </details>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p style="font-size:.9rem;color:var(--ukc-muted);margin-top:12px;">Aucune analyse pour l'instant.</p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
