<x-layouts.admin :title="$claim->reference" :subtitle="$claim->typeLabel().' · '.$claim->subject">
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
                        <span style="font-size:12px;color:var(--ukc-muted);">Source : {{ $claim->source === 'chatbot' ? 'Assistante Gesco' : 'Saisie back-office' }}</span>
                    </div>
                    <h3 style="margin:0 0 4px;">{{ $claim->subject }}</h3>
                    <div style="font-size:13px;color:var(--ukc-muted);margin-bottom:12px;">
                        {{ $claim->name ?: 'Déclarant inconnu' }} · {{ $claim->email }}
                        @if ($claim->edition) · {{ $claim->edition->colloque?->name ? $claim->edition->colloque->name.' — ' : '' }}{{ $claim->edition->title ?: $claim->edition->year }}@endif
                        · reçue le {{ $claim->created_at?->translatedFormat('d/m/Y à H:i') }}
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
                            <div class="ukc-field"><label>Statut</label>
                                <select name="status">
                                    @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($claim->status === $k)>{{ $l }}</option>@endforeach
                                </select>
                            </div>
                            <div class="ukc-field"><label>Résolution (facultatif)</label>
                                <textarea name="resolution" rows="4" placeholder="Ce qui a été fait / la réponse apportée…">{{ $claim->resolution }}</textarea>
                            </div>
                            <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Enregistrer</button>
                        </form>
                        <form method="POST" action="{{ route('admin.claims.assign', $claim) }}">
                            @csrf
                            <div class="ukc-field"><label>Assigné à</label>
                                <select name="assigned_to" onchange="this.form.submit()">
                                    <option value="">— Personne —</option>
                                    @foreach ($assignees as $u)<option value="{{ $u->id }}" @selected($claim->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach
                                </select>
                            </div>
                            <p style="font-size:12px;color:var(--ukc-muted);">Personnes habilitées (admin, gestionnaires ou délégués « réclamations » de cette édition).</p>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ===== Analyse IA ===== --}}
            <div class="ukc-card">
                <h3><x-ukc.icon name="sparkles" :size="18" /> Analyse de traitement (IA)</h3>
                <p style="font-size:13px;color:var(--ukc-muted);margin:4px 0 12px;">L'IA analyse le souci et propose : une résolution directe, la procédure à suivre, ou explique pourquoi c'est irrésolvable. Elle <strong>propose</strong> — vous décidez.</p>
                <form method="POST" action="{{ route('admin.claims.analyze', $claim) }}">
                    @csrf
                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="sparkles" :size="15" /> {{ $claim->analyses() ? 'Relancer l’analyse' : 'Analyser' }}</button>
                </form>

                @if (session('ukc_error'))
                    <div class="ukc-alert ukc-alert--err" style="margin-top:12px;"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ session('ukc_error') }}</div></div>
                @endif

                @forelse ($claim->analyses() as $a)
                    @php($verdict = $a['verdict'] ?? 'procedure')
                    <div style="margin-top:14px;border-top:1px solid var(--ukc-border);padding-top:12px;">
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            @if ($verdict === 'resolvable')
                                <span class="ukc-pill ukc-pill--ok">L'IA propose une résolution</span>
                            @elseif ($verdict === 'unresolvable')
                                <span class="ukc-pill ukc-pill--err">Irrésolvable en l'état</span>
                            @else
                                <span class="ukc-pill ukc-pill--warn">Procédure à suivre</span>
                            @endif
                            <span style="font-size:11.5px;color:var(--ukc-muted);">{{ $a['at'] ?? '' }} @if (! empty($a['by']))· {{ $a['by'] }}@endif</span>
                        </div>

                        @if (! empty($a['summary']))<div style="margin-top:8px;line-height:1.55;">{{ $a['summary'] }}</div>@endif

                        @if ($verdict === 'resolvable' && filled($a['resolution'] ?? ''))
                            <div style="margin-top:8px;background:var(--ukc-bg);border-radius:10px;padding:10px 12px;white-space:pre-line;line-height:1.55;">{{ $a['resolution'] }}</div>
                            <form method="POST" action="{{ route('admin.claims.status', $claim) }}" style="margin-top:8px;">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <input type="hidden" name="resolution" value="{{ $a['resolution'] }}">
                                <button type="submit" class="ukc-btn ukc-btn--green ukc-btn--sm"><x-ukc.icon name="check" :size="15" /> Utiliser cette résolution et marquer résolue</button>
                            </form>
                        @elseif ($verdict === 'procedure' && ! empty($a['steps']))
                            <ol style="margin:8px 0 0;padding-left:18px;display:flex;flex-direction:column;gap:5px;line-height:1.5;">
                                @foreach ($a['steps'] as $step)<li>{{ $step }}</li>@endforeach
                            </ol>
                        @elseif ($verdict === 'unresolvable' && filled($a['reason'] ?? ''))
                            <div style="margin-top:8px;white-space:pre-line;line-height:1.55;">{{ $a['reason'] }}</div>
                        @endif
                    </div>
                @empty
                    <p style="font-size:.9rem;color:var(--ukc-muted);margin-top:12px;">Aucune analyse pour l'instant.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.admin>
