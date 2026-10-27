<x-layouts.admin :title="'Version v'.$archive->version.' archivée'" :subtitle="'Communication '.$submission->ref_code">
    <div class="admin-page admin-submission-detail">
        @php($initials = fn ($name) => \Illuminate\Support\Str::of($name ?: '?')->explode(' ')->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: '?')
        <div class="ukc-card metabar">
            <div class="info">
                <strong>{{ $submission->ref_code }}</strong>
                - Version v{{ $archive->version }} archivée le {{ $archive->created_at?->format('d/m/Y à H:i') }}
                lors du renvoi de la version v{{ (int) $archive->version + 1 }}
            </div>
            <div class="acts">
                <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.submissions.show', $submission) }}">
                    <x-ukc.icon name="chevron" :size="14" style="transform:rotate(180deg);" /> Retour à la fiche actuelle
                </a>
                @if ($archive->file_url)
                    <a class="ukc-btn ukc-btn--sm" href="{{ $archive->file_url }}" target="_blank" rel="noopener noreferrer">
                        <x-ukc.icon name="download" :size="14" /> Ouvrir le fichier archivé
                    </a>
                @endif
            </div>
        </div>

        <div class="ukc-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                <span style="font-family:var(--ukc-mono);font-weight:700;color:var(--ukc-muted);font-size:.9rem;">Version v{{ $archive->version }}</span>
                <span class="ukc-pill ukc-pill--muted">Archivée · lecture seule</span>
            </div>
            <div class="paper-title">{{ $archive->title }}</div>
            <div class="meta-row">
                <span><strong>Type de communication :</strong> {{ $archive->type === 'poster' ? 'Poster' : 'Orale' }}</span>
                <span><strong>Mode de présentation :</strong> {{ $archive->presentationModeLabel() ?? '-' }}</span>
                @if ($archive->track)<span><strong>Axe :</strong> {{ $archive->track }}</span>@endif
                <span><strong>Événement :</strong> {{ $submission->event?->title }}</span>
            </div>

            <h3>Auteurs de cette version</h3>
            <ul class="authors-list">
                @forelse (($archive->authors ?? []) as $author)
                    <li>
                        @php($name = \App\Support\EventNameFormat::author($submission->event, $author))
                        <div class="av">{{ $initials($name) }}</div>
                        <div>
                            <div class="name">
                                {{ $name ?: '-' }}
                                @if (!empty($author['corresponding']))
                                    <span class="ukc-pill ukc-pill--info" style="margin-left:6px;">Correspondant</span>
                                @endif
                            </div>
                            @if (!empty($author['affiliation']))
                                <div class="aff">{{ $author['affiliation'] }}</div>
                            @endif
                        </div>
                    </li>
                @empty
                    <li>Aucun auteur renseigné pour cette version.</li>
                @endforelse
            </ul>

            <h3>Résumé archivé</h3>
            <div class="abstract-box ukc-rte">{!! $archive->abstract ? \App\Support\Html::render($archive->abstract) : '-' !!}</div>
            @if ($archive->keywords)
                <div class="ukc-keywords">
                    @foreach (\App\Support\SubmissionKeywords::parse($archive->keywords, (new \App\Support\SubmissionFormConfig($submission->event))->keywordSeparator()) as $keyword)
                        <span>{{ $keyword }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
