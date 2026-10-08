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
        'visible' => $guide->video_visible_to_participants,
    ])->keyBy('id');
@endphp

<x-layouts.admin title="Prise en main" subtitle="Guides pratiques pour utiliser {{ config('app.name') }}">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.dashboard') }}"><x-ukc.icon name="home" :size="14" /> Tableau de bord</a>
        @if ($canManage)
            <button class="ukc-btn ukc-btn--blue ukc-btn--sm" type="button" onclick="window.dispatchEvent(new CustomEvent('open-onboarding-video-batch'))"><x-ukc.icon name="video" :size="14" /> Ajouter les vidéos</button>
        @endif
    </x-slot:actions>

    <div class="admin-page admin-onboarding" x-data="{
        editing: null, videoRequest: null, batchOpen: false,
        guides: {{ Js::from($guidePayload) }},
        updateUrl: '{{ $updateUrl }}', requestUrl: '{{ $requestUrl }}',
        edit(id) { this.editing = {...this.guides[id], roles: [...this.guides[id].roles]}; },
        request(id) { this.videoRequest = this.guides[id]; }
    }" @open-onboarding-video-batch.window="batchOpen = true">
        @if (session('success'))<div class="ukc-alert ukc-alert--success">{{ session('success') }}</div>@endif

        <div class="ukc-stats onboarding-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="book" :size="20" /></div><div class="lbl">Guides disponibles</div><div class="val blue">{{ $guides->count() }}</div><div class="hint">Parcours de la plateforme</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="video" :size="20" /></div><div class="lbl">Vidéos publiées</div><div class="val">{{ $videosReady }}</div><div class="hint">{{ $guides->count() - $videosReady }} vidéo(s) à ajouter</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Espaces concernés</div><div class="val green">3</div><div class="hint">Admin, participant et public</div></div>
        </div>

        <section class="ukc-card onboarding-intro">
            <div class="onboarding-intro__icon"><x-ukc.icon name="book" :size="22" /></div>
            <div><h2>Centre de prise en main</h2><p>Les administrateurs de colloque peuvent adapter les textes, déposer une vidéo et choisir sa visibilité dans l’espace participant.</p></div>
        </section>

        @if ($canManage)
            <section class="onboarding-automation"><span class="onboarding-automation__icon"><x-ukc.icon name="sparkles" :size="18" /></span><div><strong>Production vidéo assistée</strong><p>Le moteur sélectionne Gemini, Claude ou OpenAI selon les réglages, prépare le scénario, puis Hyperframes rend la vidéo sur le serveur avec la charte GEVES. Aucun compte IA sur le poste de l’administrateur n’est requis.</p></div></section>
        @endif

        <div class="ukc-nav-docs" id="guides" aria-label="Catégories de guides">
            <a @class(['active' => $activeTab === 'all']) href="{{ route('admin.onboarding', ['tab' => 'all']) }}#guides">Tous <span class="nav-count">{{ $guides->count() }}</span></a>
            @foreach ($categories as $key => $category)<a @class(['active' => $activeTab === $key]) href="{{ route('admin.onboarding', ['tab' => $key]) }}#guides">{{ $category['label'] }} <span class="nav-count">{{ $counts[$key] ?? 0 }}</span></a>@endforeach
        </div>

        <div class="onboarding-guide-grid">
            @foreach ($guides as $guide)
                @continue($activeTab !== 'all' && $activeTab !== TutorialGuideCatalog::categoryFor($guide->key))
                @php($isQueued = $guide->video_generation_status === 'queued')
                @php($isGenerating = $guide->video_generation_status === 'generating')
                @php($isFailed = $guide->video_generation_status === 'failed')
                <article class="ukc-card onboarding-guide" id="{{ $guide->key }}">
                    <header class="onboarding-guide__head"><span class="onboarding-guide__icon"><x-ukc.icon :name="TutorialGuideCatalog::iconFor($guide->key)" :size="20" /></span><div><span class="onboarding-guide__number">Guide {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $guide->title }}</h2></div></header>
                    <p class="onboarding-guide__intro">{{ $guide->intro }}</p>
                    <ol class="onboarding-guide__steps">@foreach ($guide->steps ?: [] as $step)<li>{{ $step }}</li>@endforeach</ol>
                    @if ($guide->hasVideo())
                        <div class="onboarding-video onboarding-video--ready"><video controls preload="metadata" src="{{ $guide->videoUrl() }}"></video><div><strong>Vidéo de démonstration</strong><small>{{ $guide->video_visible_to_participants ? 'Visible aux participants concernés' : 'Masquée dans l’espace participant' }}</small></div></div>
                    @else
                        <div class="onboarding-video"><span><x-ukc.icon name="video" :size="18" /></span><div><strong>Vidéo de démonstration</strong><small>{{ $isQueued ? 'Demande de production enregistrée' : 'À ajouter prochainement' }}</small></div></div>
                    @endif
                    @if ($isQueued)<span class="onboarding-guide__status"><x-ukc.icon name="clock" :size="13" /> Demande enregistrée, lancement de l’agent vidéo…</span>@endif
                    @if ($isGenerating)<span class="onboarding-guide__status"><x-ukc.icon name="video" :size="13" /> Rendu vidéo Hyperframes en cours…</span>@endif
                    @if ($isFailed)<span class="onboarding-guide__status onboarding-guide__status--error"><x-ukc.icon name="alert-triangle" :size="13" /> La production a échoué. Relancez la demande ou consultez le journal.</span>@endif
                    <footer>
                        @if ($canManage)
                            <button class="ukc-btn ukc-btn--outline ukc-btn--sm" type="button" @click="edit({{ $guide->id }})"><x-ukc.icon name="settings" :size="14" /> Modifier</button>
                            <button class="ukc-btn ukc-btn--ghost ukc-btn--sm" type="button" @click="request({{ $guide->id }})"><x-ukc.icon name="video" :size="14" /> {{ $guide->hasVideo() ? 'Mettre à jour la vidéo' : 'Demander la vidéo' }}</button>
                        @else
                            <span class="onboarding-guide__access"><x-ukc.icon name="lock" :size="14" /> Modification réservée aux administrateurs</span>
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
            <form class="box narrow" method="POST" :action="videoRequest ? requestUrl.replace('__guide__', videoRequest.id) : ''">
                @csrf<input type="hidden" name="action" value="update"><input type="hidden" name="tab" value="{{ $activeTab }}"><div class="box-head"><span>Mettre à jour la vidéo</span><button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="videoRequest = null">Fermer</button></div>
                <div class="box-body"><p>Confirmez la demande de mise à jour pour <strong x-text="videoRequest?.title"></strong>. Vous pouvez préciser le changement attendu.</p><div class="ukc-form-row"><label for="video-prompt">Précision facultative</label><textarea id="video-prompt" name="prompt" placeholder="Ex. intégrer le nouveau parcours de soumission."></textarea></div></div>
                <div class="box-foot"><button type="button" class="ukc-btn ukc-btn--ghost" @click="videoRequest = null">Annuler</button><button class="ukc-btn ukc-btn--blue" type="submit">Confirmer la demande</button></div>
            </form>
        </div>

        <div class="ukc-modal" x-show="batchOpen" x-cloak @keydown.escape.window="batchOpen = false" style="display: none;">
            <form class="box narrow" method="POST" action="{{ route('admin.onboarding.videos.request-all') }}">
                @csrf<div class="box-head"><span>Ajouter les vidéos de prise en main</span><button type="button" class="ukc-btn ukc-btn--ghost ukc-btn--sm" @click="batchOpen = false">Fermer</button></div>
                <div class="box-body"><p>Une demande sera créée pour chaque guide sans vidéo. Le moteur GEVES choisit Gemini, Claude ou OpenAI pour préparer le scénario, puis Hyperframes produit la vidéo avec le logo, la palette, la police, une musique originale et des mouvements sobres.</p><div class="ukc-form-row"><label for="batch-prompt">Consigne facultative pour toutes les vidéos</label><textarea id="batch-prompt" name="prompt" placeholder="Ex. insister sur les étapes importantes pour les communicants."></textarea></div></div>
                <div class="box-foot"><button type="button" class="ukc-btn ukc-btn--ghost" @click="batchOpen = false">Annuler</button><button class="ukc-btn ukc-btn--blue" type="submit">Enregistrer les demandes</button></div>
            </form>
        </div>
    </div>
</x-layouts.admin>
