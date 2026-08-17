<div class="admin-page admin-documents" x-data="{ tab: 'attestations' }">
    {{-- Sélecteur d'édition partagé --}}
    <div class="ukc-field" style="max-width:340px;margin-bottom:16px;">
        <label>Édition</label>
        <select wire:model.live="edition_id">
            <option value="">— Choisir —</option>
            @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
        </select>
    </div>

    @if (! $edition)
        <div class="ukc-empty"><h3>Choisissez une édition</h3><p>Sélectionnez une édition pour gérer ses documents.</p></div>
    @else
        <div class="ukc-stats">
            <div class="ukc-stat-card"><div class="lbl">Participants</div><div class="val blue">{{ $counts['participants'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Communications acceptées</div><div class="val green">{{ $counts['accepted'] }}</div></div>
            <div class="ukc-stat-card"><div class="lbl">Attestations valides</div><div class="val">{{ $counts['issued'] }}</div></div>
        </div>

        <div class="ukc-tabs">
            <button type="button" @click="tab='attestations'" :class="{ active: tab==='attestations' }">Attestations</button>
            <button type="button" @click="tab='badges'" :class="{ active: tab==='badges' }">Badges</button>
            <button type="button" @click="tab='livre'" :class="{ active: tab==='livre' }">Livre des résumés</button>
        </div>

        {{-- Attestations --}}
        <div class="ukc-panel" :class="{ active: tab==='attestations' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="award" :size="18" /> Émettre des attestations</span></h2>
                <p class="desc">Attestations vérifiables par QR code. L’émission est idempotente (pas de doublon par bénéficiaire/type).</p>
                <div class="ukc-form-grid">
                    <div class="ukc-field">
                        <label for="type">Type</label>
                        <select id="type" wire:model="type">
                            @foreach ($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="ukc-field">
                        <label for="audience">Audience</label>
                        <select id="audience" wire:model="audience">
                            <option value="paid">Participants ayant payé</option>
                            <option value="communicants">Communicants (acceptés)</option>
                            <option value="all">Tous les participants</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                    <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm"
                            wire:click="issue" wire:confirm="Émettre les attestations pour l'audience choisie ?">
                        <x-ukc.icon name="award" :size="16" /> Émettre
                    </button>
                    <a href="{{ route('admin.documents.templates') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="palette" :size="16" /> Personnaliser le modèle
                    </a>
                </div>
            </div>

            <div class="ukc-card" style="padding:0;">
                <div style="padding:16px 18px;border-bottom:1px solid var(--ukc-border);">
                    <h2 style="margin:0;">Attestations émises</h2>
                </div>
                @if ($attestations->count())
                    <div class="ukc-table-wrap">
                        <table class="ukc-table">
                            <thead><tr><th>Bénéficiaire</th><th>Type</th><th>Émise le</th><th>État</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>
                                @foreach ($attestations as $a)
                                    <tr wire:key="att-{{ $a->id }}">
                                        <td><div class="title">{{ $a->recipient_name }}</div><div class="meta">{{ $a->recipient_email }}</div></td>
                                        <td><span class="ukc-pill ukc-pill--info">{{ $a->typeLabel() }}</span></td>
                                        <td>{{ $a->issued_at?->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($a->revoked)<span class="ukc-pill ukc-pill--err">Révoquée</span>
                                            @else<span class="ukc-pill ukc-pill--ok">Valide</span>@endif
                                        </td>
                                        <td style="text-align:right;white-space:nowrap;">
                                            <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="diploma" :size="14" /></a>
                                            <a href="{{ route('attestation.verify', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="check-circle" :size="14" /></a>
                                            @if ($a->revoked)
                                                <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="restore({{ $a->id }})">Rétablir</button>
                                            @else
                                                <button type="button" class="ukc-btn ukc-btn--danger ukc-btn--sm" wire:click="revoke({{ $a->id }})" wire:confirm="Révoquer cette attestation ?">Révoquer</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="ukc-empty"><h3>Aucune attestation</h3><p>Émettez des attestations ci-dessus.</p></div>
                @endif
            </div>
        </div>

        {{-- Badges --}}
        <div class="ukc-panel" :class="{ active: tab==='badges' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="card" :size="18" /> Badges des participants</span></h2>
                <p class="desc">
                    Planche de badges imprimables (A4, couleur par rôle) pour les <strong>{{ $counts['participants'] }}</strong>
                    participant(s) de l’édition. Ouvrez la planche puis Imprimer → Enregistrer en PDF.
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('admin.documents.badges', $edition) }}" target="_blank" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="card" :size="16" /> Générer les badges
                    </a>
                    <a href="{{ route('admin.badges.scan') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="video" :size="16" /> Pointage à l’accueil
                    </a>
                    <a href="{{ route('admin.documents.templates') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="palette" :size="16" /> Personnaliser le modèle
                    </a>
                </div>
            </div>
        </div>

        {{-- Livre des résumés --}}
        <div class="ukc-panel" :class="{ active: tab==='livre' }">
            <div class="ukc-card">
                <h2><span><x-ukc.icon name="book" :size="18" /> Livre des résumés</span></h2>
                <p class="desc">
                    Recueil des <strong>{{ $counts['accepted'] }}</strong> communication(s) acceptée(s), groupées par axe :
                    aperçu HTML imprimable ou export Word (.docx) à mise en page fidèle à la charte.
                </p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('admin.documents.livret', $edition) }}" target="_blank" class="ukc-btn ukc-btn--blue ukc-btn--sm">
                        <x-ukc.icon name="book" :size="16" /> Aperçu du livre
                    </a>
                    <a href="{{ route('admin.documents.livret', $edition) }}?format=docx" class="ukc-btn ukc-btn--green ukc-btn--sm">
                        <x-ukc.icon name="file" :size="16" /> Word (.docx)
                    </a>
                    <a href="{{ route('admin.documents.sessions', $edition) }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                        <x-ukc.icon name="layers" :size="16" /> Fiches de session
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
