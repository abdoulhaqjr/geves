<x-layouts.admin title="E-mails" subtitle="Campagnes, journal & rappels programmés">
    <div class="admin-page admin-emails">
        {{-- Sous-navigation (fidèle au mockup admin-emails) --}}
        <div class="ukc-nav-docs">
            <button type="button" class="active">E-mails programmés</button>
            <a href="{{ route('admin.emails.templates') }}">Modèles e-mails</a>
        </div>

        <div class="ukc-stats">
            <div class="ukc-stat-card"><div class="lbl">Total</div><div class="val blue">{{ $stats['total'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">En file</div><div class="val warn">{{ $stats['queued'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Envoyés</div><div class="val green">{{ $stats['sent'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Échecs</div><div class="val" style="color:#dc2626;">{{ $stats['failed'] }}</div></div>
        </div>

        <div class="ukc-grid-2" style="grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:18px;align-items:start;">

            {{-- Composer une campagne --}}
            <div class="ukc-card" x-data="{ audience: '{{ old('audience', 'all') }}', counts: @js($recipientCounts) }">
                <h2 style="margin:0 0 4px;font-size:1.05rem;">Composer une campagne</h2>
                <p class="ukc-muted" style="margin:0 0 16px;font-size:.85rem;">
                    Ciblez une audience et envoyez tout de suite ou programmez l’envoi.
                </p>

                <form method="POST" action="{{ route('admin.emails.send') }}" style="display:flex;flex-direction:column;gap:14px;">
                    @csrf
                    <div class="ukc-grid-2" style="grid-template-columns:1fr 1fr;gap:12px;">
                        <div class="ukc-field">
                            <label>Édition</label>
                            <select name="edition_id"
                                    onchange="window.location='{{ route('admin.emails.index') }}?edition_id='+this.value+'&status={{ $status }}'">
                                <option value="">— Édition —</option>
                                @foreach ($editions as $ed)
                                    <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                                @endforeach
                            </select>
                            @error('edition_id')<span class="ukc-field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="ukc-field">
                            <label>Audience</label>
                            <select name="audience" x-model="audience">
                                @foreach ($audiences as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="ukc-pill ukc-pill--info" style="align-self:flex-start;">
                        <x-ukc.icon name="users" :size="14" /> <span x-text="counts[audience] ?? 0"></span> destinataire(s)
                    </div>

                    <div class="ukc-field">
                        <label>Objet</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" maxlength="200" placeholder="Ex. Rappel : clôture des inscriptions">
                        @error('subject')<span class="ukc-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="ukc-field">
                        <label>Message</label>
                        <textarea name="body" rows="7" placeholder="Bonjour {prenom},&#10;&#10;…">{{ old('body') }}</textarea>
                        <span class="ukc-muted" style="font-size:.78rem;">
                            Variables : <code>{prenom}</code> <code>{nom}</code> <code>{nom_complet}</code> <code>{edition}</code>
                        </span>
                        @error('body')<span class="ukc-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="ukc-field">
                        <label>Date d’envoi <span class="ukc-muted">(vide = immédiat)</span></label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
                        @error('scheduled_at')<span class="ukc-field-error">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <button type="submit" class="ukc-btn ukc-btn--blue">
                            <x-ukc.icon name="mail" :size="18" /> Mettre en file / Envoyer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Journal des e-mails --}}
            <div class="ukc-card" style="padding:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:16px 18px;border-bottom:1px solid var(--ukc-border);">
                    <h2 style="margin:0;font-size:1.05rem;">Journal</h2>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <div class="ukc-field" style="margin:0;">
                            <select onchange="window.location='{{ route('admin.emails.index') }}?edition_id={{ $edition_id }}&status='+this.value">
                                <option value="">Tous statuts</option>
                                <option value="queued" @selected($status === 'queued')>En file</option>
                                <option value="sent" @selected($status === 'sent')>Envoyés</option>
                                <option value="failed" @selected($status === 'failed')>Échecs</option>
                            </select>
                        </div>
                        @if ($queuedDue > 0)
                            <form method="POST" action="{{ route('admin.emails.send-due') }}"
                                  onsubmit="return confirm('Envoyer maintenant les {{ $queuedDue }} e-mail(s) arrivés à échéance ?')">
                                @csrf
                                <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                    <x-ukc.icon name="clock" :size="14" /> Envoyer les dus ({{ $queuedDue }})
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if ($journal->count())
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead>
                                <tr><th>Destinataire</th><th>Objet</th><th>Programmé</th><th>Statut</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($journal as $email)
                                    <tr>
                                        <td>
                                            <div class="title">{{ $email->participant?->full_name ?: '—' }}</div>
                                            <div class="meta">{{ $email->recipient }}</div>
                                        </td>
                                        <td><div class="meta">{{ \Illuminate\Support\Str::limit($email->subject, 40) }}</div></td>
                                        <td>
                                            <div class="meta">
                                                @if ($email->status === 'sent' && $email->sent_at)
                                                    {{ $email->sent_at->format('d/m/Y H:i') }}
                                                @elseif ($email->scheduled_at)
                                                    {{ $email->scheduled_at->format('d/m/Y H:i') }}
                                                @else — @endif
                                            </div>
                                        </td>
                                        <td>
                                            @switch($email->status)
                                                @case('sent')
                                                    <span class="ukc-pill ukc-pill--ok">Envoyé</span>@break
                                                @case('queued')
                                                    <span class="ukc-pill ukc-pill--warn">En file</span>@break
                                                @case('failed')
                                                    <span class="ukc-pill ukc-pill--err" title="{{ $email->error }}">Échec</span>@break
                                                @default
                                                    <span class="ukc-pill ukc-pill--muted">{{ $email->status }}</span>
                                            @endswitch
                                        </td>
                                        <td style="text-align:right;">
                                            @if ($email->status === 'queued')
                                                <form method="POST" action="{{ route('admin.emails.cancel', $email) }}"
                                                      onsubmit="return confirm('Annuler cet e-mail ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                                        <x-ukc.icon name="trash" :size="14" />
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="padding:14px 18px;">{{ $journal->links() }}</div>
                @else
                    <div class="ukc-empty"><h3>Aucun e-mail</h3><p>Les campagnes envoyées apparaîtront ici.</p></div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
