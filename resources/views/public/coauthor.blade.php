<x-layouts.app :title="__('Frais de co-auteur')">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:.8rem;font-weight:700;color:var(--ukc-muted);text-transform:uppercase;letter-spacing:.04em;">
                {{ $edition->title ?: $edition->year }}
            </div>
            <h1 style="font-size:1.4rem;margin:6px 0 4px;color:var(--ukc-blue);">{{ __('Frais de co-auteur') }}</h1>
            <p style="color:var(--ukc-muted);font-size:.92rem;margin:0;">
                {{ __('Retrouvez par votre e-mail les communications acceptées qui vous citent, puis réglez vos frais depuis votre espace.') }}
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
            {{-- ⚠️ Conservé pour les règlements co-auteur ANCIENS : le retour de
                 passerelle d'un paiement `meta.flow = coauthor` atterrit encore ici. --}}
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
        @elseif (! empty($foundSubmissions))
            {{-- ⚠️ On ORIENTE, on n'encaisse plus. Le règlement se fait dans l'espace
                 participant, où la communication est déjà rattachée
                 (`LinkSubmissionAuthors` à la soumission, `LinkPendingAuthorships` à
                 l'inscription). Payer ici créait un SECOND participant pour la même
                 personne - et l'attestation partait sur celui-là, que son titulaire ne
                 pouvait pas consulter faute de compte. --}}
            <div class="ukc-card">
                <h2 style="font-size:1rem;margin:0 0 10px;">{{ __('Vos communications acceptées') }}</h2>
                <ul style="list-style:none;padding:0;margin:0 0 18px;display:flex;flex-direction:column;gap:8px;">
                    @foreach ($foundSubmissions as $s)
                        <li style="display:flex;gap:10px;align-items:baseline;padding:10px 12px;border:1px solid var(--ukc-border);border-radius:10px;">
                            <span class="ukc-pill ukc-pill--ok" style="font-family:var(--ukc-mono);">{{ $s['ref'] }}</span>
                            <span>{{ $s['title'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="coauthor-next">
                    <p class="lead">{!! __('Vos frais se règlent depuis <strong>votre espace participant</strong>, au tarif de votre grade.') !!}</p>
                    <ul>
                        <li><x-ukc.icon name="check" :size="16" /> {{ __('Vos communications y sont déjà rattachées.') }}</li>
                        <li><x-ukc.icon name="check" :size="16" /> {{ __('Votre attestation de communication s’y trouvera après le colloque.') }}</li>
                        <li><x-ukc.icon name="check" :size="16" /> {{ __('Votre reçu et vos documents y restent accessibles.') }}</li>
                    </ul>
                    <div class="acts">
                        <a href="{{ $registerUrl }}" class="ukc-btn ukc-btn--primary">
                            <x-ukc.icon name="user-plus" :size="16" /> {{ __('Créer mon compte') }}
                        </a>
                        <a href="{{ $loginUrl }}" class="ukc-btn ukc-btn--outline">
                            {{ __('J’ai déjà un compte') }}
                        </a>
                    </div>
                    <p class="hint">{{ __('Utilisez l’adresse ci-dessus : c’est elle qui rattache vos communications à votre compte.') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
