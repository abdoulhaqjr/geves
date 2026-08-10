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
        <div class="ukc-step">Participation</div>
        <div class="ukc-step">Attestation</div>
    </div>

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
        <div class="ukc-card" style="text-align:center;background:linear-gradient(135deg,rgba(34,62,164,.06),rgba(0,153,51,.06));">
            <h3 style="justify-content:center;">Appel à communications ouvert</h3>
            <p style="color:var(--ukc-muted);margin:6px 0 18px;">{{ $openEdition->title }}</p>
            <a href="{{ route('account.submissions.create', $openEdition->slug) }}" wire:navigate class="ukc-btn ukc-btn--primary">
                <x-ukc.icon name="upload" :size="18" /> Soumettre une communication
            </a>
        </div>
    @else
        <div class="ukc-empty">
            <h3>Votre espace se remplira bientôt</h3>
            <p>Communications, paiements, documents et attestation apparaîtront ici au fil de votre participation.</p>
            <a href="{{ route('account.profile') }}" wire:navigate class="ukc-btn ukc-btn--primary">
                <x-ukc.icon name="user" :size="18" /> Compléter mon profil
            </a>
        </div>
    @endif
</div>
