<div>
    <div class="ukc-field" style="max-width:320px;margin-bottom:18px;">
        <label>Édition</label>
        <select wire:model.live="edition_id">
            <option value="">— Choisir —</option>
            @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
        </select>
    </div>

    @if ($edition_id === '')
        <div class="ukc-empty"><h3>Choisissez une édition</h3></div>
    @else
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3><x-ukc.icon name="award" :size="20" /> Émettre des attestations</h3>
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
            <button type="button" class="ukc-btn ukc-btn--blue ukc-btn--sm" style="margin-top:12px;"
                    wire:click="issue" wire:confirm="Émettre les attestations pour l'audience choisie ?">
                <x-ukc.icon name="award" :size="16" /> Émettre
            </button>
        </div>

        <div class="ukc-card" style="padding:0;">
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
    @endif
</div>
