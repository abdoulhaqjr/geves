@php
    // ⚠️ Tout ce que la page passe au navigateur est PUBLIC pour qui l'ouvre :
    // on n'y met que la clé publique du service temps réel, jamais son secret -
    // il ne sert qu'à signer l'autorisation de canal, côté serveur.
    $docConfig = [
        'saveUrl' => route('admin.project.documents.save', [$task, $document]),
        'stateUrl' => route('admin.project.documents.state', [$task, $document]),
        'versionUrl' => route('admin.project.documents.versions.store', [$task, $document]),
        'channel' => $channel,
        'realtime' => $realtime,
        'canWrite' => $canWrite,
        'revision' => $document->revision,
        'state' => $document->state,
        'html' => $document->html,
        'savedAtLabel' => $document->saved_at?->format('H:i'),
        'csrf' => csrf_token(),
        'me' => [
            'id' => auth('web')->id(),
            'name' => auth('web')->user()?->salutationName() ?: auth('web')->user()?->name,
        ],
    ];
@endphp

<x-layouts.admin :title="$document->title" :subtitle="'Document de la tâche « '.$task->title.' »'">
    @push('head')
        @vite(['resources/css/task-document.css', 'resources/js/task-document.js'])
    @endpush

    <x-slot:actions>
        <a href="{{ route('admin.project.index', ['event_id' => $task->event_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="arrow-left" :size="16" /> Projet
        </a>
        @if ($canWrite)
            <button type="button" id="ukcDocVersion" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                <x-ukc.icon name="layers" :size="16" /> Enregistrer une version
            </button>
        @endif
    </x-slot:actions>

    <div class="admin-page ukc-doc">

        {{-- ⚠️ L'ÉTAT DE L'ENREGISTREMENT EST AFFICHÉ EN PERMANENCE. Un document
             rédigé à plusieurs n'a pas de bouton « Enregistrer » : sans cette
             ligne, personne ne sait s'il peut fermer l'onglet. --}}
        <div class="ukc-doc-bar">
            <div class="ukc-doc-bar__left">
                @if ($canWrite)
                    <div class="ukc-doc-tools" role="toolbar" aria-label="Mise en forme">
                        <button type="button" data-doc-cmd="toggleBold" title="Gras"><x-ukc.icon name="bold" :size="16" /></button>
                        <button type="button" data-doc-cmd="toggleItalic" title="Italique"><x-ukc.icon name="italic" :size="16" /></button>
                        <button type="button" data-doc-cmd="toggleHeading:2" title="Titre">H2</button>
                        <button type="button" data-doc-cmd="toggleHeading:3" title="Sous-titre">H3</button>
                        <button type="button" data-doc-cmd="toggleBulletList" title="Liste à puces"><x-ukc.icon name="list" :size="16" /></button>
                        <button type="button" data-doc-cmd="toggleOrderedList" title="Liste numérotée">1.</button>
                        <button type="button" data-doc-cmd="toggleBlockquote" title="Citation">&ldquo;</button>
                        <button type="button" data-doc-cmd="undo" title="Annuler ma dernière modification"><x-ukc.icon name="undo" :size="16" /></button>
                        <button type="button" data-doc-cmd="redo" title="Rétablir"><x-ukc.icon name="redo" :size="16" /></button>
                    </div>
                @endif
            </div>
            <div class="ukc-doc-bar__right">
                <span id="ukcDocPresence" class="ukc-doc-presence"></span>
                <span id="ukcDocEtat" class="ukc-doc-etat"></span>
            </div>
        </div>

        @unless ($realtime)
            {{-- ⚠️ On DIT que la simultanéité n'est pas branchée, au lieu de laisser
                 croire qu'elle l'est : deux personnes qui écrivent sans se voir ne
                 perdent rien (les états fusionnent à l'enregistrement), mais elles
                 doivent savoir qu'elles ne se verront pas écrire. --}}
            <div class="ukc-callout ukc-doc-note">
                <x-ukc.icon name="info" :size="18" />
                <div>
                    <strong>Rédaction simultanée non activée.</strong>
                    Vous pouvez écrire et tout est enregistré ; vous ne verrez simplement pas les autres taper en direct.
                    @can('settings')
                        Elle se branche dans <a href="{{ route('admin.settings', ['tab' => 'integrations']) }}">Réglages › Intégrations</a>.
                    @endcan
                </div>
            </div>
        @endunless

        <div class="ukc-doc-layout">
            <div class="ukc-card ukc-doc-page">
                <div id="ukcDocEditeur" class="ukc-doc-editeur"></div>
            </div>

            <aside class="ukc-doc-aside">
                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="users" :size="18" /> Qui travaille dessus</span></h2>
                    @forelse ($associates as $a)
                        <div class="ukc-doc-who">
                            <span class="dot" style="--h:{{ crc32($a['name']) % 360 }};"></span>
                            {{ $a['name'] }}
                        </div>
                    @empty
                        <p class="desc">Personne n’est encore associé à cette tâche. Ajoutez des collaborateurs depuis le tableau du projet pour qu’ils puissent écrire ici.</p>
                    @endforelse
                </div>

                <div class="ukc-card">
                    <h2><span><x-ukc.icon name="clock" :size="18" /> Versions</span></h2>
                    <p class="desc">Une version est posée automatiquement toutes les {{ \App\Models\ProjectTaskDocument::VERSION_EVERY_MINUTES }} minutes de travail, et à chaque fois que vous en demandez une.</p>
                    <ul id="ukcDocVersions" class="ukc-doc-versions">
                        @foreach ($versions as $v)
                            <li>
                                <button type="button" class="ukc-doc-version"
                                        data-version="{{ $v['id'] }}"
                                        data-version-url="{{ route('admin.project.documents.versions.show', [$task, $document, $v['id']]) }}">
                                    <strong>{{ $v['label'] ?: 'Version '.$v['revision'] }}</strong>
                                    <span>{{ collect([$v['atLabel'], $v['author']])->filter()->implode(' - ') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    @if ($versions === [])
                        <p class="desc">Aucune version pour l’instant.</p>
                    @endif
                </div>

                @if ($canWrite)
                    <form method="POST" action="{{ route('admin.project.documents.destroy', [$task, $document]) }}"
                          data-confirm="Supprimer ce document et tout son historique ? Cette action est sans retour.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">
                            <x-ukc.icon name="trash" :size="16" /> Supprimer le document
                        </button>
                    </form>
                @endif
            </aside>
        </div>
    </div>

    <script>window.UKC_DOC = @json($docConfig);</script>
</x-layouts.admin>
