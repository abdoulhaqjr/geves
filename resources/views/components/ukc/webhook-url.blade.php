@props(['url', 'label' => 'URL du webhook'])

{{-- URL de notification à déclarer chez le marchand (lecture seule + copie). --}}
<div class="ukc-field full ukc-webhook-url" x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($url)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1500); }).catch(() => {}); } }">
    <label>{{ $label }}</label>
    <div class="row">
        <input type="text" value="{{ $url }}" readonly onclick="this.select()" aria-label="{{ $label }}">
        <button type="button" class="ukc-btn ukc-btn--outline" @click="copy()"
                x-text="copied ? 'Copié' : 'Copier'">Copier</button>
    </div>
    <p class="ukc-hint">À coller dans le tableau de bord du marchand pour que les paiements soient confirmés automatiquement. Cette URL est propre à cet événement.</p>
</div>
