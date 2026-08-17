<div class="admin-page admin-participants">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <div class="ukc-field" style="min-width:200px;">
                <select wire:model.live="edition_id">
                    <option value="">— Édition —</option>
                    @foreach ($editions as $ed)<option value="{{ $ed->id }}">{{ $ed->title ?: $ed->year }}</option>@endforeach
                </select>
            </div>
            <div class="ukc-field" style="min-width:180px;">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom ou e-mail…">
            </div>
            <div class="ukc-field" style="min-width:150px;">
                <select wire:model.live="role">
                    <option value="">Tous les rôles</option>
                    @foreach ($roles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
        @if ($edition_id !== '')
            <button type="button" class="ukc-btn ukc-btn--blue" wire:click="invitePaidUninvited"
                    wire:confirm="Envoyer une invitation à tous les payés non encore invités ?">
                <x-ukc.icon name="mail" :size="18" /> Inviter les payés non invités
            </button>
        @endif
    </div>

    <div class="ukc-stats">
        <div class="ukc-stat-card"><div class="lbl">Participants</div><div class="val blue">{{ $stats['total'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Ont payé</div><div class="val green">{{ $stats['paid'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Pointés (accueil)</div><div class="val">{{ $stats['checkedIn'] }}</div></div>
        <div class="ukc-stat-card"><div class="lbl">Auteurs</div><div class="val">{{ $stats['authors'] }}</div></div>
    </div>

    <div class="ukc-card" style="padding:0;">
        @if ($participants->count())
            <div class="ukc-table-wrap">
                <table class="ukc-table">
                    <thead>
                        <tr><th>Participant</th><th>Rôle</th><th>Paiement</th><th>Invité</th><th style="text-align:right;">Action</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $p)
                            @php($paid = $p->payments->where('status', 'paid')->isNotEmpty())
                            <tr wire:key="part-{{ $p->id }}">
                                <td>
                                    <div class="title">{{ $p->full_name }}</div>
                                    <div class="meta">{{ $p->email }}</div>
                                </td>
                                <td><span class="ukc-pill ukc-pill--info">{{ $p->roleLabel() }}</span></td>
                                <td>
                                    @if ($paid)<span class="ukc-pill ukc-pill--ok">Réglé</span>
                                    @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                </td>
                                <td>
                                    @if ($p->isInvited())<span class="ukc-pill ukc-pill--ok">Oui</span>
                                    @else<span class="ukc-pill ukc-pill--muted">Non</span>@endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{ route('admin.participants.preview', $p) }}" wire:navigate
                                       class="ukc-btn ukc-btn--ghost ukc-btn--sm" title="Sa page (lecture seule)">
                                        <x-ukc.icon name="eye" :size="14" /> Sa page
                                    </a>
                                    <button type="button" class="ukc-btn ukc-btn--outline ukc-btn--sm"
                                            wire:click="invite({{ $p->id }})" @disabled(blank($p->email))>
                                        <x-ukc.icon name="mail" :size="14" /> {{ $p->isInvited() ? 'Relancer' : 'Inviter' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px;">{{ $participants->links() }}</div>
        @else
            <div class="ukc-empty"><h3>Aucun participant</h3><p>Sélectionnez une édition ou ajustez les filtres.</p></div>
        @endif
    </div>
</div>
