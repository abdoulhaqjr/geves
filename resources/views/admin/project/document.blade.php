{{--
    ⚠️⚠️ ANCIEN DOCUMENT TEXTE, EN LECTURE SEULE.

    L'éditeur Tiptap/Yjs a été retiré le 21/10/2026 : un document de tâche est
    désormais un DOCX, XLSX, PPTX ou TXT chez DocSpace. Cette page ne sert plus qu'à
    RELIRE ce qui a été rédigé avant, contenu courant et versions comprises -
    un texte qu'on cesse d'afficher est un texte perdu, et c'est en production
    qu'on s'en apercevrait. Rien ici n'écrit.
--}}
<x-layouts.admin :title="$document->title" :subtitle="'Ancien document texte de la tâche « '.$task->title.' »'">
    @push('head')
        @vite(['resources/css/task-office.css'])
    @endpush

    <x-slot:actions>
        <a href="{{ route('admin.project.index', ['event_id' => $task->event_id]) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
            <x-ukc.icon name="arrow-left" :size="16" /> Projet
        </a>
    </x-slot:actions>

    <div class="admin-page ukc-office">
        <div class="ukc-card ukc-office__unavailable">
            <h2>Lecture seule</h2>
            <p>
                Ce document a été rédigé avec l’ancien éditeur texte, qui a été retiré.
                Son contenu reste consultable ici, avec son historique.
                Pour reprendre ce travail à plusieurs, créez un document DOCX, XLSX, PPTX ou TXT
                dans la tâche et recopiez-y ce qui doit continuer.
            </p>
        </div>

        <div class="ukc-card ukc-office__texte">
            <h2><span><x-ukc.icon name="file" :size="18" /> Contenu</span></h2>
            @if (trim($html) !== '')
                <div class="ukc-office__lecture">{!! $html !!}</div>
            @else
                <p>Ce document est vide.</p>
            @endif
        </div>

        <div class="ukc-office__details">
            <section class="ukc-card">
                <h2><span><x-ukc.icon name="users" :size="18" /> Qui travaillait dessus</span></h2>
                @forelse ($associates as $associate)
                    <span class="ukc-office__person">{{ $associate['name'] }}</span>
                @empty
                    <p>Personne n’est associé à cette tâche.</p>
                @endforelse
            </section>

            <section class="ukc-card">
                <h2><span><x-ukc.icon name="clock" :size="18" /> Versions conservées</span></h2>
                @forelse ($versions as $version)
                    <details class="ukc-office__ancienne">
                        <summary>
                            <strong>{{ $version['label'] ?: 'Version '.$version['revision'] }}</strong>
                            <span>{{ collect([$version['atLabel'], $version['author']])->filter()->implode(' - ') }}</span>
                        </summary>
                        <div class="ukc-office__lecture">{!! $version['html'] ?: '<p>Version vide.</p>' !!}</div>
                    </details>
                @empty
                    <p>Aucune version conservée.</p>
                @endforelse
            </section>
        </div>

        @if ($canDelete)
            <form method="POST" action="{{ route('admin.project.documents.destroy', [$task, $document]) }}"
                  data-confirm="Supprimer ce document et tout son historique ? Cette action est sans retour."
                  class="ukc-office__delete">
                @csrf
                @method('DELETE')
                <button type="submit" class="ukc-btn ukc-btn--danger ukc-btn--sm">
                    <x-ukc.icon name="trash" :size="16" /> Supprimer le document
                </button>
            </form>
        @endif
    </div>
</x-layouts.admin>
