<div>
    {{-- Bandeau : prochaine étape --}}
    @if (! $account->hasVerifiedEmail())
        <div class="ukc-alert ukc-alert--warn">
            <span class="ico"><x-ukc.icon name="mail" :size="18" /></span>
            <div style="flex:1;">
                <strong>Confirmez votre adresse e-mail</strong> pour pouvoir soumettre une communication.
                <a href="{{ route('account.verification.notice') }}" wire:navigate
                   style="color:inherit;text-decoration:underline;font-weight:700;">Renvoyer le lien</a>
            </div>
        </div>
    @elseif (blank($account->affiliation))
        <div class="ukc-alert ukc-alert--info">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div style="flex:1;">
                <strong>Complétez votre profil</strong> (affiliation) pour finaliser votre inscription.
                <a href="{{ route('account.profile') }}" wire:navigate
                   style="color:inherit;text-decoration:underline;font-weight:700;">Compléter mon profil</a>
            </div>
        </div>
    @endif

    {{-- En-tête de bienvenue --}}
    <div class="ukc-card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div class="ukc-avatar-preview" style="width:64px;height:64px;font-size:20px;">
                @if ($account->photo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($account->photo_path) }}" alt="">
                @else
                    {{ $account->initials }}
                @endif
            </div>
            <div style="min-width:0;">
                <h2 style="margin:0;font-size:1.2rem;">Bonjour {{ $account->first_name }}</h2>
                <p style="font-size:.9rem;color:var(--ukc-muted);margin-top:2px;">
                    {{ $account->email }}
                    @if ($account->hasVerifiedEmail())
                        <span class="ukc-pill ukc-pill--ok" style="margin-left:6px;">
                            <x-ukc.icon name="check" :size="12" /> E-mail vérifié
                        </span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Étapes du parcours --}}
    <div class="ukc-steps">
        <div class="ukc-step done">Compte créé</div>
        <div @class(['ukc-step', 'done' => $account->hasVerifiedEmail(), 'active' => ! $account->hasVerifiedEmail()])>
            E-mail vérifié
        </div>
        <div @class(['ukc-step', 'done' => filled($account->affiliation), 'active' => $account->hasVerifiedEmail() && blank($account->affiliation)])>
            Profil complet
        </div>
        <div @class(['ukc-step', 'done' => $submissions->isNotEmpty() || $payments->isNotEmpty()])>Participation</div>
        <div @class(['ukc-step', 'done' => $attestations->isNotEmpty()])>Attestation</div>
    </div>

    {{-- Mes rôles & activité (dérivés inter-éditions) --}}
    @if (count($roles) || $moderatedSessions->isNotEmpty() || $reportedSessions->isNotEmpty() || $committees->isNotEmpty())
        <div class="ukc-card" style="margin-bottom:18px;">
            <h3><x-ukc.icon name="role" :size="20" /> Mes rôles &amp; activité</h3>
            @if (count($roles))
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 6px;">
                    @foreach ($roles as $role)
                        <span class="ukc-pill ukc-pill--info">{{ $role }}</span>
                    @endforeach
                </div>
                <p class="ukc-muted" style="font-size:.8rem;margin:0 0 8px;">Rôles dérivés de votre activité sur l’ensemble des éditions.</p>
            @endif

            @if ($moderatedSessions->isNotEmpty() || $reportedSessions->isNotEmpty())
                <div style="margin-top:8px;">
                    @foreach ($moderatedSessions as $s)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">Modérateur</span>
                            {{ $s->label ?: 'Session' }} <span class="ukc-muted">— {{ $s->edition?->title }}</span>
                        </div>
                    @endforeach
                    @foreach ($reportedSessions as $s)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">Rapporteur</span>
                            {{ $s->label ?: 'Session' }} <span class="ukc-muted">— {{ $s->edition?->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($committees->isNotEmpty())
                <div style="margin-top:8px;">
                    @foreach ($committees as $c)
                        <div style="padding:6px 0;font-size:.9rem;border-top:1px solid var(--ukc-border);">
                            <span class="ukc-pill ukc-pill--muted">Comité</span>
                            {{ $c->person_title ?: 'Membre' }} <span class="ukc-muted">— {{ $c->edition?->title }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($payableEdition)
        <div class="ukc-card" style="text-align:center;background:linear-gradient(135deg,rgba(0,153,51,.08),rgba(34,62,164,.06));margin-bottom:18px;">
            <h3 style="justify-content:center;">Communication acceptée</h3>
            <p style="color:var(--ukc-muted);margin:6px 0 18px;">Réglez vos frais d’inscription pour finaliser votre participation à « {{ $payableEdition->title }} ».</p>
            <a href="{{ route('account.payment', $payableEdition) }}" wire:navigate class="ukc-btn ukc-btn--green">
                <x-ukc.icon name="card" :size="18" /> Payer mes frais
            </a>
        </div>
    @endif

    @if ($openEdition)
        <div class="ukc-card" style="text-align:center;background:linear-gradient(135deg,rgba(34,62,164,.06),rgba(0,153,51,.06));margin-bottom:20px;">
            <h3 style="justify-content:center;">Appel à communications ouvert</h3>
            <p style="color:var(--ukc-muted);margin:6px 0 18px;">{{ $openEdition->title }}</p>
            <a href="{{ route('account.submissions.create', $openEdition->slug) }}" wire:navigate class="ukc-btn ukc-btn--primary">
                <x-ukc.icon name="upload" :size="18" /> Soumettre une communication
            </a>
        </div>
    @endif

    @php($hasContent = $submissions->isNotEmpty() || $payments->isNotEmpty() || $attestations->isNotEmpty())

    @if ($hasContent)
        <div class="ukc-grid-2" style="align-items:start;">
            {{-- Communications --}}
            <div class="ukc-card">
                <h3 style="justify-content:space-between;">
                    <span><x-ukc.icon name="inbox" :size="20" /> Mes communications</span>
                    <a href="{{ route('account.submissions.index') }}" wire:navigate class="ukc-btn ukc-btn--ghost ukc-btn--sm">Tout voir</a>
                </h3>
                @forelse ($submissions as $s)
                    <a href="{{ route('account.submissions.show', $s) }}" wire:navigate
                       style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                        <span style="min-width:0;">
                            <span style="font-family:ui-monospace,monospace;color:var(--ukc-blue);font-weight:700;font-size:.82rem;">{{ $s->ref_code }}</span>
                            <span style="display:block;font-weight:600;font-size:.9rem;">{{ \Illuminate\Support\Str::limit($s->title, 44) }}</span>
                        </span>
                        <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}" style="flex-shrink:0;">{{ $s->statusLabel() }}</span>
                    </a>
                @empty
                    <p style="font-size:.88rem;color:var(--ukc-muted);">Aucune communication.</p>
                @endforelse
            </div>

            {{-- Paiements & attestations --}}
            <div style="display:flex;flex-direction:column;gap:20px;">
                <div class="ukc-card">
                    <h3><x-ukc.icon name="receipt" :size="20" /> Paiements & reçus</h3>
                    @forelse ($payments as $p)
                        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                            <span style="font-size:.88rem;">
                                <strong>{{ number_format((float) $p->amount, 0, ',', ' ') }} {{ $p->currency }}</strong>
                                <span style="color:var(--ukc-muted);"> · {{ $p->edition?->year }}</span>
                            </span>
                            <a href="{{ route('account.receipt', $p) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="download" :size="14" /> Reçu
                            </a>
                        </div>
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">Aucun paiement réglé.</p>
                    @endforelse
                </div>

                <div class="ukc-card">
                    <h3><x-ukc.icon name="award" :size="20" /> Mes attestations</h3>
                    @forelse ($attestations as $a)
                        <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--ukc-border);align-items:center;">
                            <span style="font-size:.88rem;">{{ $a->typeLabel() }}
                                <span style="color:var(--ukc-muted);"> · {{ $a->edition?->year }}</span>
                            </span>
                            <a href="{{ route('attestation.document', $a->uuid) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="diploma" :size="14" /> Télécharger
                            </a>
                        </div>
                    @empty
                        <p style="font-size:.88rem;color:var(--ukc-muted);">Aucune attestation pour le moment.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif (! $openEdition)
        <div class="ukc-empty">
            <h3>Votre espace se remplira bientôt</h3>
            <p>Communications, paiements et attestation apparaîtront ici au fil de votre participation.</p>
            <a href="{{ route('account.profile') }}" wire:navigate class="ukc-btn ukc-btn--primary">
                <x-ukc.icon name="user" :size="18" /> Compléter mon profil
            </a>
        </div>
    @endif

    {{-- Documents de mes éditions --}}
    @if ($editionDocuments->isNotEmpty())
        <div class="ukc-card" style="margin-top:18px;">
            <h3><x-ukc.icon name="folder" :size="20" /> Documents de mes éditions</h3>
            @foreach ($editionDocuments as $doc)
                <div style="padding:12px 0;border-top:1px solid var(--ukc-border);">
                    <div style="font-weight:700;margin-bottom:8px;">{{ $doc['edition']->title ?: $doc['edition']->year }}</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @if ($doc['hasBooklet'])
                            <a href="{{ route('account.documents.livret', $doc['edition']) }}" target="_blank"
                               class="ukc-btn ukc-btn--outline ukc-btn--sm">
                                <x-ukc.icon name="book" :size="14" /> Livret des résumés
                            </a>
                        @endif
                        @foreach ($doc['media'] as $m)
                            <a href="{{ $m->url }}" target="_blank" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                <x-ukc.icon name="file" :size="14" /> {{ $m->title ?: $m->typeLabel() }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
