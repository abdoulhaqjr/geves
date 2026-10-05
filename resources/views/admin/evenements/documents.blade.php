<x-layouts.admin :title="'Documents - '.($event->title ?: $event->year)" subtitle="Ressources & documents de l’événement">
    <div style="max-width:820px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
            <a href="{{ route('admin.evenements.index') }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">← Événements</a>
            <a href="{{ route('admin.documents.livret', $event) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                <x-ukc.icon name="book" :size="16" /> Livret des résumés
            </a>
        </div>

        {{-- Ajouter --}}
        <div class="ukc-card" style="margin-bottom:18px;">
            <h2 style="font-size:1.05rem;margin:0 0 4px;">Ajouter un document</h2>
            <p class="ukc-muted" style="font-size:.85rem;margin:0 0 14px;">
                Ces ressources sont accessibles aux participants de l’événement depuis leur espace.
            </p>
            <form method="POST" action="{{ route('admin.evenements.documents.store', $event) }}"
                  style="display:grid;grid-template-columns:160px 1fr 1fr auto;gap:12px;align-items:end;">
                @csrf
                <div class="ukc-field" style="margin:0;">
                    <label>Type</label>
                    <select name="type">
                        @foreach ($types as $val => $lbl)<option value="{{ $val }}" @selected(old('type') === $val)>{{ $lbl }}</option>@endforeach
                    </select>
                </div>
                <div class="ukc-field" style="margin:0;">
                    <label>Intitulé</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Programme définitif">
                    @error('title') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="ukc-field" style="margin:0;">
                    <label>Lien (URL)</label>
                    <input type="url" name="url" value="{{ old('url') }}" placeholder="https://…">
                    @error('url') <span class="error">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="ukc-btn ukc-btn--blue">
                    <x-ukc.icon name="plus" :size="18" /> Ajouter
                </button>
            </form>
        </div>

        {{-- Liste --}}
        <div class="ukc-card" style="padding:0;">
            @if ($media->count())
                <div class="ukc-table-wrap">
                    <table class="ukc-table">
                        <thead><tr><th>Type</th><th>Intitulé</th><th>Lien</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($media as $m)
                                <tr>
                                    <td><span class="ukc-pill ukc-pill--info">{{ $m->typeLabel() }}</span></td>
                                    <td>{{ $m->title }}</td>
                                    <td><a href="{{ $m->url }}" target="_blank" style="color:var(--ukc-blue);">{{ \Illuminate\Support\Str::limit($m->url, 40) }}</a></td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('admin.evenements.documents.destroy', [$event, $m]) }}"
                                              onsubmit="return confirm('Retirer ce document ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                                                <x-ukc.icon name="trash" :size="14" />
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ukc-empty"><h3>Aucun document</h3><p>Ajoutez le programme, le livret, des vidéos ou d’autres ressources.</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
