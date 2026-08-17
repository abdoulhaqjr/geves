<x-layouts.app title="GESCO — Gestion des colloques scientifiques">
    <div style="min-height:100vh;display:flex;flex-direction:column;">
        {{-- Barre --}}
        <header style="display:flex;align-items:center;justify-content:space-between;gap:16px;
                       padding:16px clamp(16px,4vw,48px);background:#fff;border-bottom:1px solid var(--ukc-border);">
            <div>
                <div style="font-weight:900;color:var(--ukc-blue);font-size:1.1rem;">Colloques scientifiques</div>
                <div style="font-size:.8rem;color:var(--ukc-muted);font-weight:600;">Université de Kara</div>
            </div>
            <nav style="display:flex;gap:10px;">
                <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--outline ukc-btn--sm">Connexion</a>
                <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--blue ukc-btn--sm">Créer un compte</a>
            </nav>
        </header>

        {{-- Hero --}}
        <section style="flex:1;display:flex;align-items:center;justify-content:center;text-align:center;
                        padding:64px clamp(16px,4vw,48px);
                        background:radial-gradient(1000px 500px at 50% -10%, rgba(34,62,164,.12), transparent 60%);">
            <div style="max-width:680px;">
                <span class="ukc-pill ukc-pill--info" style="margin-bottom:18px;">Plateforme de gestion des colloques</span>
                <h1 style="font-size:clamp(1.8rem,4.5vw,2.9rem);font-weight:900;line-height:1.15;color:var(--ukc-text);letter-spacing:-.02em;">
                    Gérez vos colloques scientifiques <span style="color:var(--ukc-blue);">de bout en bout</span>
                </h1>
                <p style="font-size:1.05rem;color:var(--ukc-muted);margin-top:16px;line-height:1.6;">
                    Soumissions, relecture, paiements, créneaux, livret, badges et attestations —
                    une plateforme unique, à la charte de l'Université de Kara.
                </p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:28px;">
                    <a href="{{ route('account.register') }}" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="user-plus" :size="18" /> Créer mon compte
                    </a>
                    <a href="{{ route('account.login') }}" class="ukc-btn ukc-btn--outline">
                        <x-ukc.icon name="login" :size="18" /> Se connecter
                    </a>
                </div>
            </div>
        </section>

        <footer style="padding:20px;text-align:center;font-size:.82rem;color:var(--ukc-light);">
            © {{ date('Y') }} Université de Kara — Colloques scientifiques
        </footer>
    </div>
</x-layouts.app>
