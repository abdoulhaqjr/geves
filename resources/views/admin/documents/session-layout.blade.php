<x-layouts.admin title="Mise en page des documents de séance" :subtitle="$event->title ?: (string) $event->year">
    <x-slot:actions>
        <a href="{{ route('admin.documents.sessions', $event) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="file" :size="15" /> Aperçu fiches</a>
        <a href="{{ route('admin.documents.session-pv', $event) }}" target="_blank" class="ukc-btn ukc-btn--outline ukc-btn--sm"><x-ukc.icon name="sheet" :size="15" /> Aperçu PV</a>
        <a href="{{ route('admin.program.sessions') }}?event_id={{ $event->id }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm"><x-ukc.icon name="list" :size="15" /> Programme</a>
    </x-slot:actions>

    <div class="admin-page">
        <p style="color:var(--ukc-muted);font-size:13.5px;margin:0 0 16px;">
            Choisissez les entêtes et les colonnes des <strong>fiches de session</strong> et des <strong>procès-verbaux</strong>, et renommez-les au besoin. Ces réglages s'appliquent à cet événement.
        </p>

        <form method="POST" action="{{ route('admin.documents.session-layout.save', $event) }}">
            @csrf
            @method('PUT')

            <div class="ukc-grid-2" style="align-items:start;">
                {{-- Fiches de session --}}
                <div class="ukc-card">
                    <h3><x-ukc.icon name="file" :size="18" /> Fiches de session</h3>
                    @include('admin.documents._layout-group', ['title' => 'Entête', 'field' => 'sheet_header', 'catalog' => $headerFields, 'selected' => $sel['sheet_header'], 'labels' => $labels['sheet_header']])
                    @include('admin.documents._layout-group', ['title' => 'Colonnes du tableau', 'field' => 'sheet_columns', 'catalog' => $columns, 'selected' => $sel['sheet_columns'], 'labels' => $labels['sheet_columns']])
                </div>

                {{-- Procès-verbaux --}}
                <div class="ukc-card">
                    <h3><x-ukc.icon name="sheet" :size="18" /> Procès-verbal (rapport de séance)</h3>
                    @include('admin.documents._layout-group', ['title' => 'Entête', 'field' => 'pv_header', 'catalog' => $headerFields, 'selected' => $sel['pv_header'], 'labels' => $labels['pv_header']])
                    @include('admin.documents._layout-group', ['title' => 'Colonnes des communications', 'field' => 'pv_columns', 'catalog' => $columns, 'selected' => $sel['pv_columns'], 'labels' => $labels['pv_columns']])
                    @include('admin.documents._layout-group', ['title' => 'Sections du rapport', 'field' => 'pv_sections', 'catalog' => $pvSections, 'selected' => $sel['pv_sections'], 'labels' => $labels['pv_sections']])
                </div>
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="ukc-btn ukc-btn--blue"><x-ukc.icon name="check" :size="16" /> Enregistrer la mise en page</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
