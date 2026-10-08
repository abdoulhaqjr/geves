@php
    $staff = auth('web')->user();
    $guides = [
        ['id' => 'fonctionnement', 'icon' => 'home', 'number' => '01', 'title' => 'Comprendre la plateforme', 'intro' => 'Repérez les espaces, les rôles et le fil de travail d’un événement scientifique.', 'steps' => ['Choisissez l’événement sur lequel vous intervenez dans les listes du back-office.', 'Utilisez les modules auxquels votre fonction vous donne accès : scientifique, gestion, communication et pilotage.', 'Les droits sont liés à votre rôle dans chaque événement. Contactez un administrateur si une rubrique manque.'], 'url' => route('admin.dashboard'), 'label' => 'Ouvrir le tableau de bord', 'allowed' => true],
        ['id' => 'soumission', 'icon' => 'inbox', 'number' => '02', 'title' => 'Soumettre et suivre une communication', 'intro' => 'Le dépôt se fait depuis l’espace participant ; le suivi scientifique se fait dans le back-office.', 'steps' => ['Le participant sélectionne l’appel ouvert, complète le formulaire et confirme sa soumission.', 'Le comité consulte les soumissions, attribue les relecteurs puis enregistre les décisions.', 'Les auteurs suivent les demandes de correction, la décision et leur programmation depuis leur compte.'], 'url' => route('admin.submissions.index'), 'label' => 'Gérer les soumissions', 'allowed' => (bool) $staff?->canAnyEdition('submissions_list')],
        ['id' => 'visio', 'icon' => 'video', 'number' => '03', 'title' => 'Présenter une communication en visio', 'intro' => 'Préparez la séance, rejoignez la salle et utilisez les contrôles adaptés à votre rôle.', 'steps' => ['L’organisateur programme la séance et active son lien de visio dans le programme.', 'Le présentateur rejoint la salle depuis son espace de fonction, avec caméra et micro vérifiés.', 'Le modérateur anime les échanges et le rapporteur consigne les éléments utiles à la séance.'], 'url' => route('admin.program.index'), 'label' => 'Ouvrir le programme', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'rapport', 'icon' => 'sheet', 'number' => '04', 'title' => 'Rapporter une session', 'intro' => 'Le rapporteur documente le déroulement réel d’une séance afin de produire un compte rendu exploitable.', 'steps' => ['Accédez à votre espace Rapporteur depuis l’espace participant.', 'Renseignez les points saillants, les observations et les recommandations de la séance.', 'Le secrétariat relit, complète si besoin et valide le rapport dans le back-office.'], 'url' => route('admin.secretariat.reports'), 'label' => 'Voir les rapports de séance', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'moderation', 'icon' => 'mic', 'number' => '05', 'title' => 'Modérer une session', 'intro' => 'Le modérateur garantit le rythme de la séance et facilite les échanges avec les participants.', 'steps' => ['Vérifiez votre affectation à la séance et rejoignez-la depuis votre espace participant.', 'Présentez les intervenants, gérez les temps de parole et animez les questions ou sondages.', 'Consignez le passage des communications et, si nécessaire, la présence depuis la salle.'], 'url' => route('admin.program.index'), 'label' => 'Consulter les séances', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'colloque', 'icon' => 'calendar', 'number' => '06', 'title' => 'Gérer un colloque', 'intro' => 'Organisez les éditions d’une série, leur équipe et les étapes qui structurent l’événement.', 'steps' => ['Créez ou sélectionnez la série et l’événement daté auquel elle est rattachée.', 'Définissez l’équipe et attribuez à chacun les capacités nécessaires à sa mission.', 'Suivez le cycle : appel, évaluation, inscriptions, programme, documents puis archivage.'], 'url' => route('admin.evenements.index'), 'label' => 'Gérer les événements', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'configuration', 'icon' => 'settings', 'number' => '07', 'title' => 'Configurer un événement', 'intro' => 'Réglez les informations publiques, les échéances, les règles de soumission et les options opérationnelles.', 'steps' => ['Ouvrez la fiche de l’événement concerné depuis la liste des événements.', 'Renseignez les dates, l’appel, les axes, les paramètres d’inscription et les contenus publics.', 'Contrôlez les réglages avant publication, surtout les échéances et les informations de paiement.'], 'url' => route('admin.evenements.index'), 'label' => 'Configurer un événement', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'secretariat', 'icon' => 'folder', 'number' => '08', 'title' => 'Travailler au secrétariat', 'intro' => 'Centralisez les documents, les opérations de séance et le suivi administratif de l’événement.', 'steps' => ['Préparez les ordres du jour, rapports et documents nécessaires aux équipes.', 'Suivez les inscriptions, les paiements et les demandes qui doivent recevoir une réponse.', 'Contrôlez les documents officiels avant leur diffusion ou leur délivrance.'], 'url' => route('admin.secretariat'), 'label' => 'Ouvrir le secrétariat', 'allowed' => (bool) $staff?->canAnyEdition('editions_read')],
        ['id' => 'chat-public', 'icon' => 'message', 'number' => '09', 'title' => 'Utiliser le chatbot public', 'intro' => 'L’assistante publique répond aux questions des visiteurs et oriente les participants dans leurs démarches.', 'steps' => ['Ouvrez l’assistante comme le ferait un visiteur pour tester ses réponses et son orientation.', 'Elle s’appuie sur les informations publiées de l’événement : gardez-les à jour.', 'Les demandes non résolues peuvent être transmises au back-office comme réclamations ou incidents.'], 'url' => route('public.gesco'), 'label' => 'Ouvrir le chatbot public', 'allowed' => true],
        ['id' => 'chat-admin', 'icon' => 'sparkles', 'number' => '10', 'title' => 'Utiliser le chatbot admin', 'intro' => 'Le copilote aide à analyser la situation et à préparer des actions dans l’événement sélectionné.', 'steps' => ['Décrivez votre besoin : une analyse, une synthèse, un contrôle ou un brouillon.', 'Vérifiez toujours les résultats et les données proposées par le copilote.', 'Toute action proposée nécessite votre autorisation explicite avant son exécution.'], 'url' => route('admin.ai'), 'label' => 'Ouvrir le chatbot admin', 'allowed' => (bool) $staff?->canAnyEdition('view_dashboard')],
    ];
    $categories = [
        'fonctionnement' => 'pilotage', 'colloque' => 'pilotage', 'configuration' => 'pilotage', 'secretariat' => 'pilotage',
        'visio' => 'seances', 'rapport' => 'seances', 'moderation' => 'seances',
        'soumission' => 'accompagnement', 'chat-public' => 'accompagnement', 'chat-admin' => 'accompagnement',
    ];
    $tabs = ['all' => ['Tous', 10], 'pilotage' => ['Organisation', 4], 'seances' => ['Séances', 3], 'accompagnement' => ['Accompagnement', 3]];
    $activeTab = (string) request('tab', 'all');
    if (! array_key_exists($activeTab, $tabs)) $activeTab = 'all';
@endphp

<x-layouts.admin title="Prise en main" subtitle="Guides pratiques pour utiliser {{ config('app.name') }}">
    <x-slot:actions>
        <a class="ukc-btn ukc-btn--ghost ukc-btn--sm" href="{{ route('admin.dashboard') }}"><x-ukc.icon name="home" :size="14" /> Tableau de bord</a>
        <a class="ukc-btn ukc-btn--blue ukc-btn--sm" href="#guides"><x-ukc.icon name="book" :size="14" /> Voir les guides</a>
    </x-slot:actions>

    <div class="admin-page admin-onboarding">
        <div class="ukc-stats onboarding-stats">
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--blue"><x-ukc.icon name="book" :size="20" /></div><div class="lbl">Guides disponibles</div><div class="val blue">{{ count($guides) }}</div><div class="hint">Parcours de la plateforme</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--violet"><x-ukc.icon name="video" :size="20" /></div><div class="lbl">Vidéos à ajouter</div><div class="val">{{ count($guides) }}</div><div class="hint">Un emplacement par guide</div></div>
            <div class="ukc-stat-card tw-stat"><div class="tw-stat__icon tw-stat__icon--green"><x-ukc.icon name="users" :size="20" /></div><div class="lbl">Espaces concernés</div><div class="val green">3</div><div class="hint">Admin, participant et public</div></div>
        </div>

        <section class="ukc-card onboarding-intro">
            <div class="onboarding-intro__icon"><x-ukc.icon name="book" :size="22" /></div>
            <div>
                <h2>Centre de prise en main</h2>
                <p>Choisissez un parcours selon votre mission. Les raccourcis ouvrent les modules associés lorsque votre rôle vous y autorise.</p>
            </div>
        </section>

        <div class="ukc-nav-docs" id="guides" aria-label="Catégories de guides">
            @foreach ($tabs as $key => [$label, $count])
                @if ($activeTab === $key)
                    <button type="button" class="active">{{ $label }} <span class="nav-count">{{ $count }}</span></button>
                @else
                    <a href="{{ route('admin.onboarding', ['tab' => $key]) }}#guides">{{ $label }} <span class="nav-count">{{ $count }}</span></a>
                @endif
            @endforeach
        </div>

        <div class="onboarding-guide-grid">
            @foreach ($guides as $guide)
                @php($category = $categories[$guide['id']])
                @if ($activeTab === 'all' || $activeTab === $category)
                <article class="ukc-card onboarding-guide" id="{{ $guide['id'] }}">
                    <header class="onboarding-guide__head">
                        <span class="onboarding-guide__icon"><x-ukc.icon :name="$guide['icon']" :size="20" /></span>
                        <div><span class="onboarding-guide__number">Guide {{ $guide['number'] }}</span><h2>{{ $guide['title'] }}</h2></div>
                    </header>
                    <p class="onboarding-guide__intro">{{ $guide['intro'] }}</p>
                    <ol class="onboarding-guide__steps">@foreach ($guide['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                    <div class="onboarding-video" aria-label="Vidéo de démonstration à venir">
                        <span><x-ukc.icon name="video" :size="18" /></span><div><strong>Vidéo de démonstration</strong><small>À ajouter prochainement</small></div>
                    </div>
                    <footer>
                        @if ($guide['allowed'])
                            <a class="ukc-btn ukc-btn--outline ukc-btn--sm" href="{{ $guide['url'] }}">{{ $guide['label'] }} <x-ukc.icon name="chevron" :size="15" /></a>
                        @else
                            <span class="onboarding-guide__access"><x-ukc.icon name="lock" :size="14" /> Accès selon votre rôle</span>
                        @endif
                    </footer>
                </article>
                @endif
            @endforeach
        </div>
    </div>
</x-layouts.admin>
