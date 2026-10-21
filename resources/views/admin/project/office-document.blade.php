<x-layouts.admin :title="$document->title" :subtitle="'Document '.$document->format.' de la tâche « '.$task->title.' »'">
    @push('head')
        @vite(['resources/css/task-office.css'])
        @if ($officeScript)
            <script src="{{ $officeScript }}"></script>
        @endif
    @endpush

    <x-slot:actions>
        <a href="{{ route('admin.project.index', ['event_id' => $task->event_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="arrow-left" :size="16" /> Projet
        </a>
        <a href="{{ route('admin.project.documents.download', [$task, $document]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="download" :size="16" /> Télécharger
        </a>
    </x-slot:actions>

    <div class="admin-page ukc-office">
        <div class="ukc-office__heading">
            <div>
                <span class="ukc-office__type">{{ strtoupper($document->format) }}</span>
                <strong>{{ $document->title }}</strong>
                <p>{{ $canWrite ? 'Les collaborateurs de cette tâche peuvent modifier ce fichier ensemble.' : 'Consultation uniquement pour votre compte.' }}</p>
            </div>
            <div class="ukc-office__heading-actions">
                <span id="ukcOfficeStatus" class="ukc-office__status">{{ $editorReady ? 'Ouverture de l’éditeur…' : ($canWrite ? 'Édition indisponible' : 'Lecture seule') }}</span>
                @if ($editorReady)
                    <button type="button" id="ukcOfficeFullscreen" class="ukc-btn ukc-btn--ghost ukc-btn--sm">Plein écran</button>
                @endif
            </div>
        </div>

        @error('docspace') <p class="ukc-doc-choice__error" role="alert">{{ $message }}</p> @enderror

        @if ($editorReady)
            <div id="ukcOfficeEditorArea" class="ukc-office__editor" aria-label="Éditeur {{ strtoupper($document->format) }}">
                <div id="ukcOfficeEditor" class="ukc-office__editor-frame"></div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const status = document.getElementById('ukcOfficeStatus');
                    const editorArea = document.getElementById('ukcOfficeEditorArea');
                    const fullscreenButton = document.getElementById('ukcOfficeFullscreen');
                    if (editorArea && fullscreenButton && editorArea.requestFullscreen) {
                        fullscreenButton.addEventListener('click', function () { editorArea.requestFullscreen(); });
                    } else if (fullscreenButton) {
                        fullscreenButton.hidden = true;
                    }
                    if (!window.DocSpace || !window.DocSpace.SDK || !window.DocSpace.SDK.initEditor) {
                        status.textContent = 'SDK DocSpace inaccessible. Réessayez plus tard.';
                        status.classList.add('ukc-office__status--error');
                        return;
                    }
                    const config = @json($docspaceConfig);
                    config.events = {
                        onAppReady: function () { status.textContent = 'Éditeur prêt'; },
                        onNoAccess: function () {
                            status.textContent = 'Le lien d’édition DocSpace ne donne pas accès à ce fichier.';
                            status.classList.add('ukc-office__status--error');
                        },
                        onNotFound: function () {
                            status.textContent = 'Fichier introuvable dans DocSpace.';
                            status.classList.add('ukc-office__status--error');
                        },
                    };
                    window.DocSpace.SDK.initEditor(config);
                });
            </script>
        @else
            <div class="ukc-card ukc-office__unavailable">
                <h2>{{ $canWrite ? 'Édition en ligne indisponible' : 'Consultation du document' }}</h2>
                <p>{{ $docspaceError ?: ($canWrite
                    ? 'Le service bureautique de la plateforme doit être configuré pour ouvrir ce document à plusieurs. Le fichier reste disponible au téléchargement.'
                    : 'Vous pouvez télécharger ce document. L’éditeur partagé est réservé aux collaborateurs de la tâche.') }}</p>
                <a href="{{ route('admin.project.documents.download', [$task, $document]) }}" class="ukc-btn ukc-btn--blue">Télécharger le fichier</a>
            </div>
        @endif

        <div class="ukc-office__details">
            <section class="ukc-card">
                <h2><span><x-ukc.icon name="users" :size="18" /> Qui travaille dessus</span></h2>
                @forelse ($associates as $associate)
                    <span class="ukc-office__person">{{ $associate['name'] }}</span>
                @empty
                    <p>Associez des collaborateurs à la tâche depuis le tableau du projet.</p>
                @endforelse
            </section>

            <section class="ukc-card">
                <h2><span><x-ukc.icon name="clock" :size="18" /> Versions enregistrées</span></h2>
                <p>Les versions de ce fichier sont tenues par DocSpace. Ouvrez son historique depuis l’éditeur.</p>

                {{-- ⚠️ LE RENOUVELLEMENT DU LIEN A UN BOUTON, et c'est indispensable :
                     le jeton d'édition est partagé par les rédacteurs de la tâche et
                     reste valable hors de GEVES. Il se renouvelle d'office au retrait
                     d'un collaborateur ; ce geste couvre le reste - un lien recopié
                     dans un courriel, un poste perdu. --}}
                @if ($canRotate)
                    <form method="POST" action="{{ route('admin.project.documents.rotate-link', [$task, $document]) }}"
                          class="ukc-office__transfer"
                          data-confirm="Renouveler le lien d’édition ? Toute personne qui avait conservé l’ancien lien perdra l’accès au fichier.">
                        @csrf
                        <button type="submit" class="ukc-btn ukc-btn--outline ukc-btn--sm">
                            <x-ukc.icon name="key" :size="15" /> Renouveler le lien d’édition
                        </button>
                        <span>À faire si l’adresse d’édition a pu sortir de la plateforme.</span>
                    </form>
                @endif
            </section>
        </div>

        @if ($canDelete)
            <form method="POST" action="{{ route('admin.project.documents.destroy', [$task, $document]) }}"
                  data-confirm="Supprimer ce document et son fichier dans DocSpace ? Cette action est sans retour." class="ukc-office__delete">
                @csrf
                @method('DELETE')
                <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm"><x-ukc.icon name="trash" :size="16" /> Supprimer le document</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
