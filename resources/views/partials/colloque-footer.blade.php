{{-- Pied de page PROPRE À UN COLLOQUE (barre d'info), distinct du footer plateforme.
     Attend $e (l'édition courante). Data-driven depuis $e->settings. --}}
@php
    $cfSettings = $e->settings ?? [];
    $cfContact = is_array($cfSettings['contact'] ?? null) ? $cfSettings['contact'] : [];
    $cfDocuments = is_array($cfSettings['documents'] ?? null) ? $cfSettings['documents'] : [];
    // Réseaux sociaux : ceux du colloque, sinon fallback sur les réglages du site.
    $cfSocial = is_array($cfSettings['social'] ?? null) ? array_filter($cfSettings['social']) : [];
    if (empty($cfSocial)) {
        $cfSocial = array_filter([
            'facebook' => \App\Support\Settings::get('site.facebook'),
            'x' => \App\Support\Settings::get('site.x'),
            'youtube' => \App\Support\Settings::get('site.youtube'),
            'linkedin' => \App\Support\Settings::get('site.linkedin'),
        ]);
    }
    $cfFormat = $cfSettings['format'] ?? null;
    $cfSocialPaths = [
        'facebook' => 'M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z',
        'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.727-8.833L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        'youtube' => 'M23.5 6.2a3.02 3.02 0 00-2.12-2.14C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.38.56A3.02 3.02 0 00.5 6.2 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.8 3.02 3.02 0 002.12 2.14c1.88.56 9.38.56 9.38.56s7.5 0 9.38-.56a3.02 3.02 0 002.12-2.14A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z',
        'linkedin' => 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 11-.01-4.12 2.06 2.06 0 01.01 4.12zM7.12 20.45H3.56V9h3.56v11.45zM22.23 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.46c.98 0 1.77-.77 1.77-1.73V1.73C24 .77 23.21 0 22.23 0z',
    ];
    $cfSocialLabels = ['facebook' => 'Facebook', 'x' => 'X (Twitter)', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'];
@endphp
<div class="ukc-info-bar">
    <div class="ukc-container">
        <div class="ukc-info-grid">
            @if (! empty($cfContact['email']) || ! empty($cfContact['phone']))
                <div>
                    <strong>Contact organisation</strong>
                    @if (! empty($cfContact['email']))<a href="mailto:{{ $cfContact['email'] }}" style="color:inherit;">{{ $cfContact['email'] }}</a>@endif
                    @if (! empty($cfContact['phone']))<br>{{ $cfContact['phone'] }}@endif
                </div>
            @endif
            @if ($e->location)
                <div>
                    <strong>Lieu</strong>
                    <span style="white-space:pre-line;">{{ $e->location }}</span>
                </div>
            @endif
            @if ($cfFormat)
                <div>
                    <strong>Format</strong>
                    <span style="white-space:pre-line;">{{ $cfFormat }}</span>
                </div>
            @endif
            @if (count($cfDocuments))
                <div>
                    <strong>Documents</strong>
                    @foreach ($cfDocuments as $doc)
                        <a href="{{ $doc['url'] ?? '#' }}" style="color:var(--ukc-blue);font-weight:600;"
                           @if (! empty($doc['url']) && \Illuminate\Support\Str::startsWith($doc['url'], 'http')) target="_blank" rel="noopener" @endif>{{ $doc['label'] }}</a>@if (! $loop->last)<br>@endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ukc-info-bottom">
            <div>
                @if (count($cfSocial))
                    <strong style="display:block;color:var(--ukc-blue);font-size:13px;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:8px;">Suivez-nous</strong>
                    <div class="ukc-social">
                        @foreach ($cfSocial as $key => $url)
                            @if (isset($cfSocialPaths[$key]))
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $cfSocialLabels[$key] ?? $key }}">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="{{ $cfSocialPaths[$key] }}"/></svg>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            <div>© {{ now()->year }} {{ $e->colloque?->name ?? 'Université de Kara' }} — {{ $e->title }}</div>
        </div>
    </div>
</div>
