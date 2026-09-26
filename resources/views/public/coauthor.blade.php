<x-layouts.app :title="__('Paiement co-auteur')">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $edition->title ?: $edition->year }}
            </div>
            <h1 style="font-size:1.4rem;margin:6px 0 4px;color:var(--ukc-blue);">{{ __('Paiement des frais - co-auteur') }}</h1>
            <p style="color:var(--ukc-muted);font-size:.92rem;margin:0;">
                {{ __('Retrouvez vos communications acceptées et réglez vos frais au tarif de votre grade.') }}
            </p>
        </div>

        {{-- Recherche par e-mail (GET) --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <form method="GET" action="{{ route('public.coauthor', $edition->slug) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                <div class="ukc-field" style="flex:1;min-width:220px;margin:0;">
                    <label>{{ __('Votre e-mail de co-auteur') }}</label>
                    <input type="email" name="email" value="{{ $email }}" placeholder="prenom.nom@example.org" autocomplete="email" required>
                </div>
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="search" :size="18" /> {{ __('Rechercher') }}
                </button>
            </form>
        </div>

        @if ($searched && empty($foundSubmissions))
            <div class="ukc-alert ukc-alert--warn">
                <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                <div>{!! __('Aucune communication <strong>acceptée</strong> ne vous associe comme co-auteur avec cet e-mail. Vérifiez l’adresse utilisée lors de la soumission.') !!}</div>
            </div>
        @elseif ($hasPaid)
            <div class="ukc-card" style="text-align:center;">
                <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;background:#dcfce7;color:#166534;
                            display:flex;align-items:center;justify-content:center;">
                    <x-ukc.icon name="check" :size="28" />
                </div>
                <h2 style="justify-content:center;">{{ __('Frais déjà réglés') }}</h2>
                <p style="color:var(--ukc-muted);">{{ __('Merci, votre participation est à jour. Votre attestation de communication vous sera délivrée.') }}</p>
                @if ($paidReceipt)
                    <p style="margin-top:8px;font-size:.9rem;">{{ __('Reçu n°') }} <strong style="font-family:var(--ukc-mono);">{{ $paidReceipt->receipt_no }}</strong></p>
                @endif
            </div>
        @elseif (session('pay_instructions'))
            <div class="ukc-card">
                <div class="ukc-alert ukc-alert--ok" style="margin-bottom:0;">
                    <span class="ico"><x-ukc.icon name="check-circle" :size="18" /></span>
                    <div>{{ session('pay_instructions') }}</div>
                </div>
            </div>
        @elseif (! empty($foundSubmissions))
            <div class="ukc-card" x-data="{ grade: @js(old('grade', $grade)), fees: @js($feeMap), highest: {{ $highestAmount ?: 'null' }},
                     get amount() { return (this.fees[this.grade] ?? (this.grade ? this.highest : null)); } }">
                <h2 style="font-size:1rem;margin:0 0 10px;">{{ __('Vos communications acceptées') }}</h2>
                <ul style="list-style:none;padding:0;margin:0 0 18px;display:flex;flex-direction:column;gap:8px;">
                    @foreach ($foundSubmissions as $s)
                        <li style="display:flex;gap:10px;align-items:baseline;padding:10px 12px;border:1px solid var(--ukc-border);border-radius:10px;">
                            <span class="ukc-pill ukc-pill--ok" style="font-family:var(--ukc-mono);">{{ $s['ref'] }}</span>
                            <span>{{ $s['title'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('public.coauthor.pay', $edition->slug) }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="ukc-field">
                        <label>{{ __('Votre grade') }} <span class="ukc-muted">{{ __('(détermine le montant)') }}</span></label>
                        @if (count($feeGrid))
                            <select name="grade" x-model="grade">
                                <option value="">- {{ __('Choisir') }} -</option>
                                @foreach ($feeGrid as $fee)
                                    <option value="{{ $fee['label'] }}">{{ $fee['label'] }} - {{ number_format($fee['amount'], 0, ',', ' ') }} FCFA</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="grade" x-model="grade" placeholder="{{ __('Votre grade') }}">
                        @endif
                        @error('grade') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;
                                padding:14px 16px;border-radius:12px;background:var(--ukc-bg);border:1px solid var(--ukc-border);margin:16px 0 18px;">
                        <span style="font-weight:600;">{{ __('Montant à régler') }}</span>
                        <span style="font-size:1.35rem;font-weight:900;color:var(--ukc-blue);"
                              x-text="amount ? (new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA') : '-'"></span>
                    </div>

                    @error('pay') <div class="ukc-alert ukc-alert--err"><span class="ico"><x-ukc.icon name="info" :size="18" /></span><div>{{ $message }}</div></div> @enderror

                    @if (count($gateways) === 0)
                        <div class="ukc-alert ukc-alert--warn" style="margin-bottom:0;">
                            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
                            <div>{{ __('Aucun moyen de paiement en ligne n’est configuré. Contactez l’organisation.') }}</div>
                        </div>
                    @else
                        <p style="font-size:.9rem;color:var(--ukc-muted);margin-bottom:12px;">{{ __('Choisissez un moyen de paiement :') }}</p>
                        <x-ukc.pay-operator :gateways="$gateways"
                                            style="width:100%;padding:9px 12px;border:1px solid var(--ukc-border,#e5e7eb);border-radius:9px;margin-bottom:12px;" />
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            @foreach ($gateways as $gateway)
                                <button type="submit" name="gateway" value="{{ $gateway->key() }}" class="ukc-btn ukc-btn--outline"
                                        style="justify-content:space-between;" :disabled="!amount">
                                    <span style="display:inline-flex;align-items:center;gap:8px;">
                                        <x-ukc.icon name="card" :size="18" /> {{ $gateway->label() }}
                                    </span>
                                    <x-ukc.icon name="chevron" :size="16" />
                                </button>
                            @endforeach
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--ukc-muted);margin-top:14px;">
                            <x-ukc.icon name="lock" :size="14" /> {{ __('Paiement sécurisé - un reçu vous sera envoyé par e-mail.') }}
                        </div>
                    @endif
                </form>
            </div>
        @endif
    </div>
</x-layouts.app>
