<div>
    <div class="ukc-grid-4" style="margin-bottom:22px;">
        <div class="ukc-stat-card">
            <div class="num">{{ $colloques }}</div>
            <div class="lbl">Colloques</div>
        </div>
        <div class="ukc-stat-card">
            <div class="num">{{ $editions }}</div>
            <div class="lbl">Éditions</div>
        </div>
        <div class="ukc-stat-card">
            <div class="num">{{ $openEditions }}</div>
            <div class="lbl">Éditions ouvertes</div>
        </div>
        <div class="ukc-stat-card">
            <div class="num">{{ $submissions }}</div>
            <div class="lbl">Soumissions</div>
        </div>
    </div>

    <div class="ukc-card">
        <h2><x-ukc.icon name="calendar" :size="20" /> Démarrage rapide</h2>
        <p style="font-size:.92rem;color:var(--ukc-muted);line-height:1.6;margin-bottom:16px;">
            Créez une édition, renseignez son thème et ses dates, ouvrez les soumissions, puis publiez sa page.
        </p>
        <a href="{{ route('admin.editions.index') }}" wire:navigate class="ukc-btn ukc-btn--primary">
            <x-ukc.icon name="calendar" :size="18" /> Gérer les éditions
        </a>
    </div>
</div>
