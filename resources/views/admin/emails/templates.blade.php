<x-layouts.admin title="Modèles d’e-mails" subtitle="Sujets & contenus des e-mails transactionnels">
    <div class="admin-page admin-emails" style="max-width:820px;" x-data="{ tpl: @js($initial), defaults: @js($defaults) }">
        <div class="ukc-nav-docs">
            <a href="{{ route('admin.emails.index') }}">E-mails programmés</a>
            <button type="button" class="active">Modèles e-mails</button>
        </div>

        <div class="ukc-muted" style="font-size:.85rem;margin-bottom:16px;">
            Placeholders :
            @foreach ($placeholders as $ph)<code>{{ $ph }}</code>@if (! $loop->last) @endif @endforeach
        </div>

        <div class="ukc-alert ukc-alert--info" style="margin-bottom:18px;">
            <span class="ico"><x-ukc.icon name="info" :size="18" /></span>
            <div>Personnalisez le sujet, le titre et l’introduction. Les détails (références, dates, boutons)
                et la mise en page restent générés automatiquement. Un champ vide reprend le texte par défaut.</div>
        </div>

        <form method="POST" action="{{ route('admin.emails.templates.update') }}">
            @csrf
            @method('PUT')
            @foreach ($labels as $key => $label)
                <div class="ukc-card" style="margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                        <h2 style="font-size:1rem;margin:0;color:var(--ukc-blue);">{{ $label }}</h2>
                        <button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm"
                                @click="tpl['{{ $key }}'] = JSON.parse(JSON.stringify(defaults['{{ $key }}']))">
                            <x-ukc.icon name="pen" :size="14" /> Défaut
                        </button>
                    </div>
                    <div class="ukc-field">
                        <label>Objet</label>
                        <input type="text" name="templates[{{ $key }}][subject]" x-model="tpl['{{ $key }}'].subject">
                        @error("templates.{$key}.subject") <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label>Titre (en-tête de l’e-mail)</label>
                        <input type="text" name="templates[{{ $key }}][heading]" x-model="tpl['{{ $key }}'].heading">
                        @error("templates.{$key}.heading") <span class="error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ukc-field">
                        <label>Introduction</label>
                        <textarea rows="3" name="templates[{{ $key }}][intro]" x-model="tpl['{{ $key }}'].intro"></textarea>
                        @error("templates.{$key}.intro") <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endforeach

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="check" :size="18" /> Enregistrer les modèles
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
