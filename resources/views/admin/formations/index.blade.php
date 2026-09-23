<x-layouts.admin title="Formations" subtitle="Module de formations d'une édition - formateurs, frais, lieu, dates (inscriptions, page publique, e-mails, attestations et visio à venir).">
    <div class="admin-page admin-formations">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
            <div class="ukc-field" style="max-width:340px;margin:0;">
                <label>Édition</label>
                <select onchange="window.location='{{ route('admin.formations') }}?edition_id='+this.value">
                    <option value="">- Choisir -</option>
                    @foreach ($editions as $ed)
                        <option value="{{ $ed->id }}" @selected((string) $ed->id === $edition_id)>{{ $ed->title ?: $ed->year }}</option>
                    @endforeach
                </select>
            </div>
            @if ($edition)
                <form method="POST" action="{{ route('admin.formations.module', $edition) }}">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $moduleEnabled ? '0' : '1' }}">
                    <button type="submit" class="ukc-btn {{ $moduleEnabled ? 'ukc-btn--outline' : 'ukc-btn--green' }}">
                        <x-ukc.icon name="{{ $moduleEnabled ? 'close' : 'check' }}" :size="16" />
                        {{ $moduleEnabled ? 'Désactiver le module' : 'Activer le module' }}
                    </button>
                </form>
            @endif
        </div>

        @if ($edition_id === '' || ! $edition)
            <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer ses formations.</p></div>
        @elseif (! $moduleEnabled)
            <div class="ukc-empty">
                <h3>Module Formations désactivé</h3>
                <p>Activez le module (bouton ci-dessus) pour proposer des formations sur cette édition.</p>
            </div>
        @else
            <div class="ukc-grid-2" style="align-items:start;gap:18px;">
                {{-- Nouvelle formation --}}
                <div class="ukc-card">
                    <h2>Nouvelle formation</h2>
                    <form method="POST" action="{{ route('admin.formations.store', $edition) }}">
                        @csrf
                        @include('admin.formations._fields', ['f' => null])
                        <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="book" :size="16" /> Créer la formation</button>
                    </form>
                </div>

                {{-- Formations existantes --}}
                <div class="ukc-card">
                    <h2>Formations <span class="ukc-badge-count">{{ $formations->count() }}</span></h2>
                    @forelse ($formations as $f)
                        <div class="ukc-subcard" style="border:1px solid var(--ukc-border,#e5e7eb);border-radius:10px;padding:14px;margin-bottom:14px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                                <div>
                                    <strong style="font-size:15px;">{{ $f->title }}</strong>
                                    @if ($f->theme)<div style="font-size:13px;color:var(--ukc-muted);">{{ $f->theme }}</div>@endif
                                    <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap;">
                                        <span class="ukc-pill {{ $f->published ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->published ? 'Active' : 'Brouillon' }}</span>
                                        @if ($f->public)<span class="ukc-pill ukc-pill--info">Publique</span>@endif
                                        <span class="ukc-pill {{ $f->isFree() ? 'ukc-pill--ok' : 'ukc-pill--muted' }}">{{ $f->isFree() ? 'Gratuite' : number_format((float) $f->fee, 0, ',', ' ').' F' }}</span>
                                    </div>
                                    <div style="font-size:12.5px;color:var(--ukc-light);margin-top:6px;">
                                        @if ($f->starts_at){{ $f->starts_at->format('d/m/Y H:i') }}@if ($f->ends_at) - {{ $f->ends_at->format('d/m/Y H:i') }}@endif @endif
                                        @if ($f->location) · {{ $f->location }}@endif @if ($f->room) · {{ $f->room }}@endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.formations.destroy', $f) }}" onsubmit="return confirm('Supprimer cette formation ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ukc-btn--icon" title="Supprimer" aria-label="Supprimer la formation"><x-ukc.icon name="trash" :size="16" /></button>
                                </form>
                            </div>

                            {{-- Formateurs --}}
                            <div style="margin-top:12px;">
                                <div style="font-size:12.5px;font-weight:600;color:var(--ukc-muted);margin-bottom:6px;">Formateurs</div>
                                @forelse ($f->trainers as $t)
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;padding:3px 0;">
                                        <span>{{ $t->name }}@if ($t->email) <span style="color:var(--ukc-light);">· {{ $t->email }}</span>@endif @if ($t->is_moderator)<span class="ukc-pill ukc-pill--info" style="margin-left:4px;">modérateur</span>@endif</span>
                                        <form method="POST" action="{{ route('admin.formations.trainers.destroy', [$f, $t]) }}" onsubmit="return confirm('Retirer ce formateur ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ukc-btn--icon" title="Retirer" aria-label="Retirer le formateur"><x-ukc.icon name="close" :size="14" /></button>
                                        </form>
                                    </div>
                                @empty
                                    <p style="font-size:12.5px;color:var(--ukc-light);margin:0;">Aucun formateur.</p>
                                @endforelse
                                <form method="POST" action="{{ route('admin.formations.trainers.store', $f) }}" style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
                                    @csrf
                                    <input type="text" name="name" placeholder="Nom du formateur" required maxlength="200" style="flex:1;min-width:140px;">
                                    <input type="email" name="email" placeholder="E-mail (facultatif)" maxlength="200" style="flex:1;min-width:140px;">
                                    <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="plus" :size="14" /> Ajouter</button>
                                </form>
                            </div>

                            {{-- Inscrits + message (convocation / rappel) --}}
                            <div style="margin-top:12px;">
                                <div style="font-size:12.5px;font-weight:600;color:var(--ukc-muted);margin-bottom:4px;">
                                    Inscrits : {{ $f->enrollments_count }}@if (! $f->isFree()) · {{ $f->paid_count }} réglé(s)@endif
                                </div>
                                @if ($f->enrollments_count > 0)
                                    <details>
                                        <summary style="cursor:pointer;font-size:13px;color:var(--ukc-blue,#223ea4);">Contacter les inscrits</summary>
                                        <form method="POST" action="{{ route('admin.formations.message', $f) }}" style="margin-top:8px;">
                                            @csrf
                                            <input type="text" name="subject" placeholder="Objet" required maxlength="200" style="width:100%;margin-bottom:6px;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;">
                                            <textarea name="body" rows="3" placeholder="Message (convocation, rappel, lien visio…)" required maxlength="5000" style="width:100%;margin-bottom:6px;padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;"></textarea>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                <select name="audience" style="padding:6px 8px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:8px;">
                                                    <option value="all">Tous les inscrits</option>
                                                    <option value="paid">Inscrits ayant réglé</option>
                                                </select>
                                                <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="mail" :size="14" /> Envoyer</button>
                                            </div>
                                        </form>
                                    </details>
                                @endif
                            </div>

                            {{-- Édition de la formation --}}
                            <details style="margin-top:10px;">
                                <summary style="cursor:pointer;font-size:13px;color:var(--ukc-blue,#223ea4);">Modifier</summary>
                                <form method="POST" action="{{ route('admin.formations.update', $f) }}" style="margin-top:10px;">
                                    @csrf @method('PUT')
                                    @include('admin.formations._fields', ['f' => $f])
                                    <button type="submit" class="ukc-btn ukc-btn--blue ukc-btn--sm"><x-ukc.icon name="check" :size="14" /> Enregistrer</button>
                                </form>
                            </details>
                        </div>
                    @empty
                        <p style="font-size:13.5px;color:var(--ukc-light);">Aucune formation pour l’instant.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
