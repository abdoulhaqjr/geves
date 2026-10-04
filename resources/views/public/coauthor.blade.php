<x-layouts.app :title="__('Paiement co-auteur')">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $edition->title ?: $edition->year }}
            </div>
            <h1 style="font-size:1.4rem;margin:6px 0 4px;color:var(--ukc-blue);">{{ __('Paiement des frais - co-auteur') }}</h1>
            <p style="color:var(--ukc-muted);font-size:.92rem;margin:0;">
                {{ __('Retrouvez vos communications acceptées par votre e-mail, et réglez vos frais au tarif de votre grade.') }}
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
            {{-- ⚠️ Les montants sont indexés par GRADE, pas par catégorie tarifaire :
                 la vue n'a jamais à rapprocher un grade d'une catégorie, `Fees` est
                 seule à savoir le faire (couverture explicite, puis libellé exact,
                 puis tarif par défaut). --}}
            <div class="ukc-card" x-data="{ grade: @js(old('grade', $grade)), montants: @js($amountByGrade),
                     get amount() { return this.montants[this.grade] ?? null; } }">
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

                    {{-- ⚠️ ON NE CHOISIT PAS SON TARIF. Le menu proposait les
                         *catégories tarifaires* avec leur montant en face : un
                         co-auteur y prenait la moins chère, et le grade déclaré dans
                         la soumission - l'information dont le logiciel se sert partout
                         ailleurs - ne servait à rien. Quand le grade est connu il est
                         montré comme un FAIT, pas comme un champ ; le serveur ne lit
                         même pas ce qui serait posté à sa place. --}}
                    @if ($gradeConnu)
                        <div class="ukc-field">
                            <label>{{ __('Votre grade') }}</label>
                            <div class="coauthor-grade">
                                <strong>{{ $grade }}</strong>
                                <span>{{ __('déclaré lors de la soumission') }}</span>
                            </div>
                            <p class="ukc-hint" style="margin-top:6px;">
                                {{ __('C’est lui qui détermine le montant. Une erreur ? Contactez l’organisation avant de régler.') }}
                            </p>
                        </div>
                    @else
                        <div class="ukc-field">
                            <label>{{ __('Votre grade') }} <span class="ukc-muted">{{ __('(il détermine le montant)') }}</span></label>
                            <select name="grade" x-model="grade">
                                <option value="">- {{ __('Indiquez votre grade') }} -</option>
                                @foreach ($gradeOptions as $gradeOption)
                                    <option value="{{ $gradeOption }}">{{ $gradeOption }}</option>
                                @endforeach
                            </select>
                            @error('grade') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;
                                padding:14px 16px;border-radius:12px;background:var(--ukc-bg);border:1px solid var(--ukc-border);margin:16px 0 0;">
                        <span style="font-weight:600;">{{ __('Montant à régler') }}</span>
                        {{-- Grade connu = montant connu AVANT le rendu : il est écrit en
                             clair, pas calculé par le navigateur. Le calcul côté client ne
                             sert qu'au grade qu'on est en train de saisir. --}}
                        @if ($gradeConnu)
                            <span style="font-size:1.35rem;font-weight:900;color:var(--ukc-blue);">{{ number_format((float) $amount, 0, ',', ' ') }} FCFA</span>
                        @else
                            <span style="font-size:1.35rem;font-weight:900;color:var(--ukc-blue);"
                                  x-text="amount ? (new Intl.NumberFormat('fr-FR').format(Math.round(amount)) + ' FCFA') : '-'"></span>
                        @endif
                    </div>
                    {{-- Aucune catégorie ne couvre ce grade : le montant affiché est le
                         plus élevé de la grille, par repli. Le taire reviendrait à faire
                         payer un tarif qu'aucune règle ne justifie. --}}
                    @if ($amountIsFallback)
                        <p class="ukc-hint" style="margin:8px 0 0;color:#b45309;">
                            {{ __('Aucune catégorie tarifaire ne couvre ce grade : le tarif le plus élevé de la grille s’applique. Contactez l’organisation si ce n’est pas le vôtre.') }}
                        </p>
                    @endif
                    <div style="margin-bottom:18px;"></div>

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
