@php
    use App\Support\TutorialGuideCatalog;

    $categories = TutorialGuideCatalog::categories();
    $activeTab = (string) request('tab', 'all');
    if ($activeTab !== 'all' && ! array_key_exists($activeTab, $categories)) $activeTab = 'all';
    $counts = $guides->countBy(fn ($guide) => TutorialGuideCatalog::categoryFor($guide->key));
    $videosReady = $guides->filter->hasVideo()->count();
    $updateUrl = route('admin.onboarding.guides.update', '__guide__');
    $requestUrl = route('admin.onboarding.videos.request', '__guide__');
    $guidePayload = $guides->map(fn ($guide) => [
        'id' => $guide->id, 'title' => $guide->title, 'intro' => $guide->intro,
        'steps' => implode("\n", $guide->steps ?: []), 'audience' => $guide->audience,
        'roles' => $guide->participant_roles ?: [], 'videoUrl' => $guide->video_url,
        'visible' => $guide->video_visible_to_participants, 'hasVideo' => $guide->hasVideo(),
    ])->keyBy('id');
@endphp

<x-layouts.admin title="Prise en main" subtitle="Guides pratiques pour prendre en main {{ config('app.name') }}">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.dashboard') }}"><x-ukc.icon name="home" :size="14" /> Tableau de bord</a>
        @if ($canManage)
            <button class="ukc-btn ukc-btn--blue ukc-btn--sm" type="button" onclick="window.dispatchEvent(new CustomEvent('open-onboarding-video-batch'))"><x-ukc.icon name="video" :size="14" /> Produire les vidéos</button>
        @endif
    </x-slot:actions>

    <div class="admin-page ukc-onb" x-data="{
        editing: null, videoRequest: null, batchOpen: false,
        guides: {{ Js::from($guidePayload) }},
        updateUrl: '{{ $updateUrl }}', requestUrl: '{{ $requestUrl }}',
        edit(id) { this.editing = {...this.guides[id], roles: [...this.guides[id].roles]}; },
        request(id) { const guide = this.guides[id]; this.videoRequest = {...guide, action: guide.hasVideo ? 'update' : 'generate', musicChoice: 'signature'}; }
    }" @open-onboarding-video-batch.window="batchOpen = true">
        @if (session('success'))<div class="ukc-alert ukc-alert--success">{{ session('success') }}</div>@endif

        <div class="ukc-stats onb-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="book" :size="20" /></div><div class="lbl">Guides disponibles</div><div class="val blue">{{ $guides->count() }}</div><div class="hint">Parcours de la plateforme</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="video" :size="20" /></div><div class="lbl">Vidéos publiées</div><div class="val">{{ $videosReady }}</div><div class="hint">{{ $guides->count() - $videosReady }} vidéo(s) à produire</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Espaces concernés</div><div class="val green">3</div><div class="hint">Administration, participant et public</div></div>
        </div>

        @if ($canManage)
            <div class="onb-note">
                <span class="onb-note__icon"><x-ukc.icon name="sparkles" :size="18" /></span>
                <div><strong>Production vidéo assistée</strong><p>Le moteur choisit Gemini, Claude ou OpenAI selon vos réglages, prépare le scénario, capture les écrans réels avec un compte de démonstration, puis Hyperframes rend la vidéo sur le serveur avec la charte {{ config('app.name') }}. Aucun compte IA n’est requis sur votre poste.</p></div>
            </div>
        @endif

        <div class="ukc-nav-docs" id="guides" aria-label="Catégories de guides">
            <a @class(['active' => $activeTab === 'all']) href="{{ route('admin.onboarding', ['tab' => 'all']) }}#guides">Tous <span class="nav-count">{{ $guides->count() }}</span></a>
            @foreach ($categories as $key => $category)<a @class(['active' => $activeTab === $key]) href="{{ route('admin.onboarding', ['tab' => $key]) }}#guides">{{ $category['label'] }} <span class="nav-count">{{ $counts[$key] ?? 0 }}</span></a>@endforeach
        </div>

        <div class="onb-grid">
            @foreach ($guides as $guide)
                @continue($activeTab !== 'all' && $activeTab !== TutorialGuideCatalog::categoryFor($guide->key))
                @php($category = TutorialGuideCatalog::categoryFor($guide->key))
                @php($status = $guide->video_generation_status)
                <article class="ukc-card onb-card" id="{{ $guide->key }}">
                    <header class="onb-card__head">
                        <span class="onb-card__icon"><x-ukc.icon :name="TutorialGuideCatalog::iconFor($guide->key)" :size="21" /></span>
                        <div class="onb-card__titles">
                            <h3 class="onb-card__title">{{ $guide->title }}</h3>
                            <span class="onb-card__tag"><x-ukc.icon :name="$categories[$category]['icon'] ?? 'book'" :size="12" /> {{ $categories[$category]['label'] ?? 'Guide' }}</span>
                        </div>
                    </header>
                    <p class="onb-card__intro">{{ $guide->intro }}</p>
                    <ol class="onb-steps">@foreach ($guide->steps ?: [] as $step)<li>{{ $step }}</li>@endforeach</ol>

                    @if ($guide->hasVideo())
                        <div class="onb-media">
                            <video controls preload="metadata" src="{{ $guide->videoUrl() }}"></video>
                            <div class="onb-media__meta">
                                <span class="onb-media__ico"><x-ukc.icon name="check" :size="15" /></span>
                                <div><strong>Vidéo de démonstration</strong><small>{{ $guide->video_visible_to_participants ? 'Visible par les participants concernés' : 'Masquée dans l’espace participant' }}</small></div>
                            </div>
                        </div>
                    @else
                        <div class="onb-media onb-media--soon">
                            <span class="onb-media__play"><x-ukc.icon name="video" :size="17" /></span>
                            <div><strong>Vidéo de démonstration</strong><small>{{ $status === 'queued' ? 'Demande de production enregistrée' : 'À produire' }}</small></div>
                        </div>
                    @endif

                    @if ($status === 'queued')<span class="onb-flag"><x-ukc.icon name="clock" :size="13" /> Demande enregistrée, lancement de l’agent vidéo…</span>@endif
                    @if ($status === 'generating')<span class="onb-flag"><x-ukc.icon name="video" :size="13" /> Rendu Hyperframes en cours…</span>@endif
                    @if ($status === 'failed')<span class="onb-flag onb-flag--error"><x-ukc.icon name="alert-triangle" :size="13" /> La production a échoué. Relancez la demande.</span>@endif

                    <footer class="onb-card__foot">
                        @if ($canManage)
                            <button class="ukc-btn ukc-btn--outline ukc-btn--sm" type="button" @click="edit({{ $guide->id }})"><x-ukc.icon name="settings" :size="14" /> Modifier le texte</button>
                            <button class="ukc-btn ukc-btn--ghost ukc-btn--sm" type="button" @click="request({{ $guide->id }})"><x-ukc.icon name="video" :size="14" /> {{ $guide->hasVideo() ? 'Mettre à jour la vidéo' : 'Produire la vidéo' }}</button>
                        @else
                            <span class="onb-card__lock"><x-ukc.icon name="lock" :size="14" /> Modification réservée aux administrateurs</span>
                        @endif
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="ukc-modal" x-show="editing" x-cloak @keydown.escape.window="editing = null" style="display: none;">
            <form class="box wide onboarding-guide-editor" method="POST" enctype="multipart/form-data" :action="editing ? updateUrl.replace('__guide__', editing.id) : ''">
                @csrf @method('PUT')<input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="box-head"><span>Modifier le guide</span><button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="editing = null">Fermer</button></div>
                <div class="box-body">
                    <div class="ukc-form-row"><label for="guide-title">Titre</label><input id="guide-title" name="title" required x-model="editing.title"></div>
                    <div class="ukc-form-row"><label for="guide-intro">Introduction</label><textarea id="guide-intro" name="intro" required x-model="editing.intro"></textarea></div>
                    <div class="ukc-form-row"><label for="guide-steps">Étapes, une par ligne</label><textarea id="guide-steps" name="steps_text" required x-model="editing.steps"></textarea></div>
                    <div class="ukc-form-grid"><div class="ukc-form-row"><label for="guide-audience">Espace concerné</label><select id="guide-audience" name="audience" x-model="editing.audience"><option value="admin">Administration</option><option value="participant">Participant</option><option value="both">Administration et participant</option></select></div><div class="ukc-form-row"><label for="guide-video-url">URL directe d’une vidéo</label><input id="guide-video-url" type="url" name="video_url" placeholder="https://…" x-model="editing.videoUrl"></div></div>
                    <div class="ukc-form-row"><label for="guide-video">Ou téléverser une vidéo</label><input id="guide-video" type="file" name="video" accept="video/mp4,video/webm,video/quicktime"><small class="onboarding-field-hint">MP4, WebM ou MOV, 500 Mo maximum.</small></div>
                    <div class="ukc-form-row"><label>Rôles participant concernés</label><div class="onboarding-role-options">@foreach (['all' => 'Tous', 'author' => 'Communicant', 'coauthor' => 'Co-auteur', 'reviewer' => 'Relecteur', 'committee' => 'Comité', 'moderator' => 'Modérateur', 'rapporteur' => 'Rapporteur', 'attendee' => 'Auditeur'] as $role => $label)<label><input type="checkbox" name="participant_roles[]" value="{{ $role }}" x-model="editing.roles"> {{ $label }}</label>@endforeach</div></div>
                    <label class="onboarding-checkbox"><input type="checkbox" name="video_visible_to_participants" value="1" x-model="editing.visible"> Rendre la vidéo visible aux participants concernés</label><label class="onboarding-checkbox onboarding-checkbox--danger"><input type="checkbox" name="remove_video" value="1"> Retirer la vidéo actuelle</label>
                </div>
                <div class="box-foot"><button type="button" class="ukc-btn ukc-btn--ghost" @click="editing = null">Annuler</button><button class="ukc-btn ukc-btn--blue" type="submit">Enregistrer le guide</button></div>
            </form>
        </div>

        <div class="ukc-modal" x-show="videoRequest" x-cloak @keydown.escape.window="videoRequest = null" style="display: none;">
            <form class="box narrow onboarding-video-request" method="POST" enctype="multipart/form-data" :action="videoRequest ? requestUrl.replace('__guide__', videoRequest.id) : ''">
                @csrf<input type="hidden" name="action" :value="videoRequest?.action"><input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="box-head"><div class="onboarding-video-request__title"><span><x-ukc.icon name="video" :size="19" /></span><div><strong x-text="videoRequest?.action === 'update' ? 'Mettre à jour la vidéo' : 'Produire la vidéo' "></strong><small x-text="videoRequest?.title"></small></div></div><button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="videoRequest = null">Fermer</button></div>
                <div class="box-body"><p x-text="videoRequest?.action === 'update' ? 'Une nouvelle version remplacera la vidéo publiée après le rendu.' : 'Le guide sera produit sous forme de vidéo dès que la demande sera confirmée.'"></p><div class="onboarding-video-request__workflow"><span><x-ukc.icon name="monitor" :size="17" /></span><div><strong>Captures réelles de {{ config('app.name') }}</strong><small>Le serveur ouvre les pages avec un compte de démonstration, puis les intègre à la vidéo avec la charte et la musique.</small></div></div><div class="ukc-form-row"><label for="video-music">Musique</label><select id="video-music" name="music_choice" x-model="videoRequest.musicChoice"><option value="signature">Signature {{ config('app.name') }} — instrumentale chaleureuse</option><option value="calm">Calme — institutionnelle et discrète</option><option value="dynamic">Dynamique — moderne et rythmée</option><option value="none">Sans musique</option></select><small class="onboarding-field-hint">La signature est utilisée par défaut.</small></div><div class="ukc-form-row"><label for="video-music-file">Ou utiliser votre musique</label><input id="video-music-file" type="file" name="music" accept="audio/mpeg,audio/mp4,audio/aac,audio/wav,audio/x-wav"><small class="onboarding-field-hint">MP3, M4A, AAC ou WAV, 25 Mo maximum. Le fichier remplace le choix ci-dessus.</small></div><div class="ukc-form-row"><label for="video-prompt">Précision facultative</label><textarea id="video-prompt" name="prompt" placeholder="Ex. mettre l’accent sur le nouveau parcours de soumission."></textarea><small class="onboarding-field-hint">Cette précision oriente le scénario sans modifier les textes du guide.</small></div></div>
                <div class="box-foot"><button type="button" class="ukc-btn ukc-btn--ghost" @click="videoRequest = null">Annuler</button><button class="ukc-btn ukc-btn--blue" type="submit" x-text="videoRequest?.action === 'update' ? 'Lancer la mise à jour' : 'Lancer la production'"></button></div>
            </form>
        </div>

        <div class="ukc-modal" x-show="batchOpen" x-cloak @keydown.escape.window="batchOpen = false" style="display: none;">
            <form class="box narrow" method="POST" enctype="multipart/form-data" action="{{ route('admin.onboarding.videos.request-all') }}">
                @csrf<div class="box-head"><span>Produire les vidéos de prise en main</span><button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="batchOpen = false">Fermer</button></div>
                <div class="box-body"><p>Une demande sera créée pour chaque guide sans vidéo. Le moteur choisit Gemini, Claude ou OpenAI pour préparer le scénario, capture les vrais écrans avec un compte de démonstration, puis Hyperframes produit la vidéo avec le logo, la palette, la police, une musique originale et des mouvements sobres.</p><div class="ukc-form-row"><label for="batch-music">Musique par défaut</label><select id="batch-music" name="music_choice"><option value="signature">Signature {{ config('app.name') }} — instrumentale chaleureuse</option><option value="calm">Calme — institutionnelle et discrète</option><option value="dynamic">Dynamique — moderne et rythmée</option><option value="none">Sans musique</option></select></div><div class="ukc-form-row"><label for="batch-music-file">Ou utiliser votre musique pour toutes les vidéos</label><input id="batch-music-file" type="file" name="music" accept="audio/mpeg,audio/mp4,audio/aac,audio/wav,audio/x-wav"><small class="onboarding-field-hint">MP3, M4A, AAC ou WAV, 25 Mo maximum.</small></div><div class="ukc-form-row"><label for="batch-prompt">Consigne facultative pour toutes les vidéos</label><textarea id="batch-prompt" name="prompt" placeholder="Ex. insister sur les étapes importantes pour les communicants."></textarea></div></div>
                <div class="box-foot"><button type="button" class="ukc-btn ukc-btn--ghost" @click="batchOpen = false">Annuler</button><button class="ukc-btn ukc-btn--blue" type="submit">Enregistrer les demandes</button></div>
            </form>
        </div>
    </div>
</x-layouts.admin>
