{{--
    **Corriger & renvoyer une communication.**

    ⚠️⚠️ Ce formulaire vivait AU BAS DE LA FICHE (`show.blade.php`), après
    l'historique, les documents liés et le créneau de passage : le bouton
    « Corriger & renvoyer » n'était qu'une ancre `#resubmit`, et l'auteur se
    retrouvait devant un formulaire surgi au milieu d'une page de consultation.

    ⚠️ **Les champs reprennent ceux du DÉPÔT**, et pas une approximation : le
    résumé passe par le même `partials.abstract-editor` (CKEditor + compteur de
    mots), les mots-clés par le même Tom Select, le fichier par le même
    `<x-ukc.filedrop>`. Le résumé était un `<textarea>` nu, sans compteur, alors
    que le serveur refuse hors des bornes de mots de l'événement : on corrigeait
    à l'aveugle, et la mise en forme du résumé se perdait au passage.

    ⚠️ **Seuls les champs que la resoumission APPLIQUE sont ici** (titre, résumé,
    mots-clés, fichier - cf. `SubmissionController::resubmit()`). L'axe, le type
    et les auteurs ne sont pas rééditables par ce circuit : les montrer en
    saisie promettrait un enregistrement qui n'a pas lieu. Ils sont rappelés en
    lecture, avec le chemin pour les changer quand il existe.
--}}
<x-layouts.participant :title="__('Corriger & renvoyer')" :subtitle="__('Version corrigée de votre communication')">
    @php
        $s = $submission;
        $keywordHelp = match ($keywordSeparator) {
            'semicolon' => __('Entrée ou point-virgule pour ajouter'),
            'both' => __('Entrée, virgule ou point-virgule pour ajouter'),
            default => __('Entrée ou virgule pour ajouter'),
        };
    @endphp

    <div class="account-submission-revise">
        <div class="rev-topbar">
            <a href="{{ route('account.submissions.show', $s) }}" class="ukc-btn ukc-btn--ghost ukc-btn--sm">
                ← {{ __('Retour à la communication') }}
            </a>
            <div class="rev-topbar__pills">
                <span class="ukc-pill ukc-pill--{{ $s->statusPill() }}">{{ $s->statusLabel() }}</span>
                <span class="ukc-pill ukc-pill--muted" style="font-family:var(--ukc-mono);">{{ $s->ref_code }}</span>
            </div>
        </div>

        <div class="ukc-alert ukc-alert--warn rev-intro">
            <span class="ico"><x-ukc.icon name="pen" :size="18" /></span>
            <div>
                {!! __('Une <strong>correction</strong> vous est demandée. Modifiez votre communication ci-dessous, puis renvoyez-la au comité.') !!}
                {{-- ⚠️ On dit ce que « renvoyer » fait VRAIMENT : la version
                     actuelle est archivée, le numéro de version avance et la
                     communication repart en évaluation. Sans cela, on hésite à
                     cliquer de peur d'écraser son travail. --}}
                <span class="rev-intro__note">
                    {{ __('Votre version actuelle (version :v) est conservée dans l’historique ; la communication repart en évaluation.', ['v' => $s->version]) }}
                </span>
            </div>
        </div>

        <div class="rev-grid">
            <form method="POST" action="{{ route('account.submissions.resubmit', $s) }}"
                  enctype="multipart/form-data" class="ukc-card rev-form">
                @csrf

                <h2><x-ukc.icon name="info" :size="20" /> {{ __('Informations générales') }}</h2>
                <div class="ukc-form-grid">
                    <div class="ukc-field full">
                        <label for="title">{{ $config->label('title') }} <span class="req">*</span></label>
                        <input type="text" id="title" name="title" maxlength="{{ $config->titleMax() }}"
                               value="{{ old('title', $s->title) }}">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <h2 style="margin-top:22px;"><x-ukc.icon name="pen" :size="20" /> {{ __('Résumé') }}</h2>
                <div class="ukc-field full">
                    <label>{{ $config->label('abstract') }} <span class="req">*</span></label>
                    @include('partials.abstract-editor', [
                        'name' => 'abstract',
                        'initial' => old('abstract', $s->abstract),
                        'min' => $config->abstractMin(),
                        'max' => $config->abstractMax(),
                        'help' => $config->help('abstract') ?: null,
                    ])
                    @error('abstract') <span class="error">{{ $message }}</span> @enderror
                </div>

                @if ($config->enabled('keywords'))
                    <div class="ukc-field full" style="margin-top:14px;">
                        <label for="keywords">{{ $config->label('keywords') }} @if ($config->required('keywords'))<span class="req">*</span>@endif</label>
                        <input type="text" id="keywords" name="keywords"
                               data-tom data-tom-create data-tom-max="{{ $config->keywordsMax() }}"
                               data-keyword-separator="{{ $keywordSeparator }}"
                               placeholder="{{ __('Saisir un mot-clé puis Entrée…') }}"
                               value="{{ old('keywords', \App\Support\SubmissionKeywords::join($s->keywords, $keywordSeparator)) }}">
                        <span class="hint">{{ $keywordHelp }}</span>
                        @error('keywords') <span class="error">{{ $message }}</span> @enderror
                    </div>
                @endif

                @if ($config->enabled('file'))
                    <h2 style="margin-top:22px;"><x-ukc.icon name="file" :size="20" /> {{ __('Fichier joint') }}</h2>
                    @if ($s->file_url)
                        {{-- ⚠️ On montre le fichier DÉJÀ déposé : sans lui, on ne sait
                             pas si « optionnel » veut dire « il n'y en a pas » ou
                             « le précédent est conservé ». C'est le second. --}}
                        <p class="hint rev-file-current">
                            <x-ukc.icon name="file" :size="14" />
                            {{ __('Fichier actuel :') }}
                            <a href="{{ $s->file_url }}" target="_blank" rel="noopener">{{ __('ouvrir') }}</a>
                            - {{ __('laissez vide pour le conserver.') }}
                        </p>
                    @endif
                    <div class="ukc-field full">
                        <label for="file">{{ __('Nouveau fichier (PDF, optionnel)') }}</label>
                        <x-ukc.filedrop name="file" id="file" accept=".pdf" :hint="__('PDF')" />
                        @error('file') <span class="error">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="ukc-form-actions rev-actions">
                    <button type="submit" class="ukc-btn ukc-btn--blue">
                        <x-ukc.icon name="upload" :size="16" /> {{ __('Renvoyer la communication') }}
                    </button>
                    <a href="{{ route('account.submissions.show', $s) }}" class="ukc-btn ukc-btn--ghost">{{ __('Annuler') }}</a>
                </div>
            </form>

            <aside class="rev-side">
                {{-- ⚠️ Les remarques À CÔTÉ du formulaire : elles vivaient en haut
                     d'une fiche longue, et corriger obligeait à faire l'aller-retour
                     de mémoire. C'est le point de l'écran. --}}
                <div class="ukc-card">
                    <h2>{{ __('Ce que demande le comité') }}</h2>
                    @forelse ($feedback as $fb)
                        <div class="msg-box">
                            <div class="from">{{ $fb['label'] ?? __('Comité scientifique') }}</div>
                            <p>{{ $fb['comments'] }}</p>
                        </div>
                    @empty
                        <div class="msg-box">
                            <div class="from">{{ __('Secrétariat scientifique') }}</div>
                            <p>{{ __('Aucun commentaire n’a été transmis avec la demande de correction. Reprenez votre communication et renvoyez-la.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="ukc-card">
                    <h2>{{ __('Rappel') }}</h2>
                    <dl class="ukc-dl">
                        <dt>{{ __('Référence') }}</dt>
                        <dd style="font-family:var(--ukc-mono);">{{ $s->ref_code }}</dd>
                        <dt>{{ $config->label('type') }}</dt>
                        <dd>{{ $s->type === 'poster' ? __('Poster') : __('Orale') }}</dd>
                        @if (filled($s->track))
                            <dt>{{ $config->label('track') }}</dt>
                            <dd>{{ $s->track }}</dd>
                        @endif
                        <dt>{{ __('Version') }}</dt>
                        <dd>{{ $s->version }}</dd>
                    </dl>
                    {{-- ⚠️ Ces trois-là ne se corrigent PAS par la resoumission : on
                         le dit, plutôt que de laisser chercher un champ absent. --}}
                    <p class="hint" style="margin-top:12px;">
                        {{ __('L’axe, le type de communication et les auteurs ne se modifient pas par ce formulaire : écrivez-le au comité dans votre résumé ou contactez le secrétariat.') }}
                    </p>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.participant>
