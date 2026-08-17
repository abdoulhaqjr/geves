<div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <p style="color:var(--ukc-muted);margin:0;">Scannez le QR d’un badge (ou saisissez son code) pour enregistrer la présence à l’accueil.</p>
        <span class="ukc-pill ukc-pill--info">{{ $checkedCount }} / {{ $totalCount }} pointé(s)</span>
    </div>

    @if ($message)
        <div class="ukc-alert ukc-alert--{{ $messageType === 'ok' ? 'ok' : 'err' }}">
            <span class="ico"><x-ukc.icon name="{{ $messageType === 'ok' ? 'check-circle' : 'info' }}" :size="18" /></span>
            <div>{{ $message }}</div>
        </div>
    @endif

    <div class="ukc-grid-2">
        {{-- Scanner + saisie manuelle --}}
        <div class="ukc-card">
            <h2><x-ukc.icon name="video" :size="20" /> Scanner un badge</h2>
            <div x-data @scan="$wire.lookup($event.detail)">
                <div id="badge-scanner" class="badge-scanner" wire:ignore></div>
            </div>
            <div class="ukc-field" style="margin-top:14px;">
                <label for="code">Saisie manuelle du code <span class="hint">(sous le QR, ex. 3-42)</span></label>
                <form wire:submit="lookup" style="display:flex;gap:8px;">
                    <input type="text" id="code" wire:model="code" placeholder="édition-participant" style="flex:1;">
                    <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="search" :size="16" /> Chercher</button>
                </form>
            </div>
        </div>

        {{-- Résultat --}}
        <div class="ukc-card">
            <h2><x-ukc.icon name="user" :size="20" /> Participant</h2>
            @if ($found)
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                    <div class="ukc-user-chip"><div class="av">{{ \Illuminate\Support\Str::of($found['name'])->explode(' ')->map(fn ($w) => \Illuminate\Support\Str::substr($w, 0, 1))->take(2)->implode('') }}</div></div>
                    <div style="min-width:0;">
                        <div style="font-weight:800;font-size:1.05rem;">{{ $found['name'] }}</div>
                        <div style="font-size:.85rem;color:var(--ukc-muted);">{{ $found['role'] }}{{ $found['institution'] ? ' · '.$found['institution'] : '' }}</div>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px;">
                    @if ($found['paid'])<span class="ukc-pill ukc-pill--ok">Réglé</span>@else<span class="ukc-pill ukc-pill--muted">Paiement en attente</span>@endif
                    @if ($found['checkedIn'])<span class="ukc-pill ukc-pill--info">Pointé à {{ $found['checkedAt'] }}</span>@else<span class="ukc-pill ukc-pill--soon">Non pointé</span>@endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="ukc-btn ukc-btn--green" wire:click="checkIn" @disabled($found['checkedIn'])>
                        <x-ukc.icon name="check" :size="18" /> {{ $found['checkedIn'] ? 'Déjà présent' : 'Marquer présent' }}
                    </button>
                    <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" wire:click="clearFound">Suivant</button>
                </div>
            @else
                <div class="ukc-empty" style="padding:28px 12px;">
                    <p style="margin:0;">Aucun badge scanné pour l’instant. Présentez un QR à la caméra ou saisissez un code.</p>
                </div>
            @endif

            @if ($recent)
                <div style="margin-top:16px;border-top:1px solid var(--ukc-border);padding-top:12px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--ukc-light);margin-bottom:8px;">Derniers pointages</div>
                    @foreach ($recent as $r)
                        <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0;">
                            <span>{{ $r['name'] }}</span><span style="color:var(--ukc-muted);">{{ $r['at'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
