@php
    /** @var \App\Models\Event $event */
    $primary = $opts['color_primary'];
    $muted = '#6b7280';
    $isGen = $general ?? false;
    $hm = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('H:i') : '';
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Livret des résumés - {{ $event->title }}</title>
    @include('documents._fonts', ['pdf' => true])
    <style>
        @page { margin: 2.2cm 2.2cm 2.4cm; }
        * { box-sizing: border-box; }
        body { font-family: {!! $opts['font_body'] !!}; font-size: {{ $opts['size_body'] }}pt; line-height: {{ $opts['line_height'] }}; color: #1f2937; }
        h1, h2, h3, .sans { font-family: {!! $opts['font_head'] !!}; }
        .page-break { page-break-before: always; }
        .center { text-align: center; }
        .muted { color: {{ $muted }}; }
        .banner { width: 100%; margin: 10px 0 18px; }
        .banner td { height: 8px; }
        h1.part { font-size: 16pt; color: {{ $primary }}; border-bottom: 2px solid #ffd700; padding-bottom: 4px; margin: 0 0 14px; }
        h2.axis { font-size: {{ $opts['size_axis'] }}pt; color: {{ $primary }}; text-transform: uppercase; margin: 18px 0 8px; }
        .ref { font-size: 9pt; font-weight: bold; color: {{ $muted }}; text-align: center; margin: 14px 0 2px; }
        /* Dompdf applique aux titres Jost des métriques verticales généreuses :
           cet interligne évite les deux lignes trop écartées du PDF. */
        .abs-title { font-size: {{ $opts['size_abstract'] }}pt; line-height: .9; font-weight: bold; color: {{ $primary }}; margin: 0 0 3px; }
        .authors { font-size: {{ $opts['size_authors'] }}pt; color: {{ $muted }}; margin: 0 0 2px; }
        .aff { font-size: 8.5pt; font-style: italic; color: {{ $muted }}; margin: 0; }
        .corr { font-size: 8.5pt; color: {{ $muted }}; margin: 3px 0 4px; }
        .lbl { font-size: 9pt; font-weight: bold; color: {{ $primary }}; margin: 6px 0 2px; }
        .abs-body { text-align: {{ $opts['align_body'] }}; }
        .abs-body p { margin: 0 0 6px; }
        .kw { font-size: 10pt; margin: 4px 0 0; }
        table.prog { width: 100%; border-collapse: collapse; margin: 4px 0 10px; }
        table.prog th, table.prog td { border: 1px solid #ddd; padding: 4px 7px; font-size: 9.5pt; text-align: left; }
        table.prog th { background: #f1f5f9; color: {{ $primary }}; }
        .idx { font-size: 10pt; margin: 0 0 3px; }
        .idx b { color: #111827; }
        .abs-entry { margin-top: 10px; }
        /* L'en-tête (réf. -> correspondant) reste entier. Le libellé « Résumé »
           reste avec le début du texte, qui peut se poursuivre sur la page suivante. */
        .abs-head, .axis-start { page-break-inside: avoid; }
        .abs-resume .lbl { page-break-after: avoid; }
        /* Aucune LIGNE ne se coupe en deux (bandeau de jour ou donnée). */
        table.prog tr { page-break-inside: avoid; }
        /* L'en-tête d'un tableau de programme (bandeau de jour + colonnes) reste
           SOLIDAIRE de sa 1re ligne de données : ce <tbody> insécable descend ensemble
           à la page suivante plutôt que de laisser l'en-tête seul en bas de page. Les
           lignes suivantes (second <tbody>) se coupent librement. On n'applique PAS
           avoid à tout le tableau (cela creusait un trou avant lui). */
        table.prog tbody.prog-start { page-break-inside: avoid; }
        /* Le titre d'atelier colle au début de son tableau (pas de titre seul en bas). */
        .keep-with-next { page-break-after: avoid; }
    </style>
    @if ($isGen)
    {{-- Palette EXACTE du « Document général du colloque » : titres noirs,
         ateliers terracotta, tableaux à en-tête gris et bandeau de jour bleu. --}}
    {{-- Couleurs issues de la SOURCE UNIQUE `LivretDocx::generalCssPalette()`
         (partagée avec le .docx) : ne pas recopier d'hexadécimaux en dur ici. --}}
    <style>
        h1.part { color: {{ $palette['black'] }}; border-bottom: none; }
        h2.axis { color: {{ $palette['black'] }}; }
        {{-- Dispositions EXACTES du document de référence : code à droite, titre /
            auteurs / affiliations / correspondant CENTRÉS. --}}
        .ref { color: {{ $palette['black'] }}; text-align: right; font-size: 10.5pt; font-weight: bold; margin: 16px 0 2px; }
        .abs-title { color: {{ $palette['black'] }}; text-align: center; }
        .authors { color: {{ $palette['black'] }}; font-weight: bold; font-style: italic; font-size: 11pt; text-align: center; }
        .aff { color: {{ $palette['black'] }}; font-style: italic; text-align: center; }
        .corr { color: {{ $palette['black'] }}; font-style: italic; text-align: center; }
        .lbl { color: {{ $palette['black'] }}; }
        .kw .muted { color: {{ $palette['black'] }} !important; }
        table.prog th { background: {{ $palette['head_fill'] }}; color: {{ $palette['black'] }}; }
        table.prog th, table.prog td { border-color: {{ $palette['border'] }}; }
        .atelier-head { color: {{ $palette['terracotta'] }}; }
        td.day-banner { background: {{ $palette['day_fill'] }}; color: {{ $palette['black'] }}; font-weight: bold; text-align: center; }
        td.session-banner { background: {{ $palette['session_fill'] }}; color: {{ $palette['black'] }}; font-weight: bold; text-align: center; }
        td.session-roles { background: #ffffff; color: {{ $palette['black'] }}; font-size: 9pt; }
        tr.grp td { background: {{ $palette['head_fill'] }}; font-weight: bold; text-align: center; }
    </style>
    @endif
</head>
<body>

{{-- ===================== PAGE DE GARDE ===================== --}}
<div style="padding-top: 90px;">
    @if ($opts['cover_logo_data'])
        <p class="center" style="margin:0 0 14px;"><img src="{{ $opts['cover_logo_data'] }}" alt="Logo de couverture" style="max-width:140px;max-height:100px;"></p>
    @endif
    @if ($name = trim((string) $event->series?->name))
        <p class="center sans" style="font-weight:bold;color:{{ $primary }};font-size:12pt;margin:0 0 6px;">{{ \Illuminate\Support\Str::upper($name) }}</p>
    @endif
    <h1 class="sans center" style="font-size:{{ $opts['size_cover'] }}pt;margin:30px 0 6px;color:#111827;">{{ $event->title }}</h1>
    @if ($label = trim((string) $event->event_label))
        <p class="center muted" style="margin:0 0 6px;">{{ $label }}</p>
    @endif
    @if ($opts['show_theme'] && ($theme = trim((string) $event->theme)) !== '')
        <p class="center" style="font-style:italic;color:{{ $primary }};">« {{ $theme }} »</p>
    @endif
    @if ($opts['show_dates'] && $event->date_start)
        <p class="center muted" style="margin-top:8px;">
            {{ $event->date_start->format('d/m/Y') }}@if ($event->date_end) - {{ $event->date_end->format('d/m/Y') }}@endif
            @if ($loc = trim((string) $event->location)) &nbsp;-&nbsp; {{ $loc }} @endif
        </p>
    @endif
    <p class="center sans" style="font-weight:bold;color:{{ $opts['color_secondary'] }};font-size:13pt;margin-top:60px;">{{ $opts['cover_mention'] }}</p>
</div>

{{-- ===================== HAUT PATRONAGE ===================== --}}
@if ($opts['patronage'] !== '')
    <div class="page-break" style="padding-top:60px;">
        @foreach (preg_split('/\R+/', $opts['patronage']) ?: [] as $i => $line)
            @if (trim($line) !== '')
                <p class="center" style="font-size:12pt;margin:0 0 6px;@if ($i === 0)font-weight:bold;margin-top:60px;@endif">{{ trim($line) }}</p>
            @endif
        @endforeach
    </div>
@endif

{{-- ===================== REMERCIEMENTS ===================== --}}
@if ($opts['acknowledgements'] !== '')
    <div class="page-break">
        <h1 class="part sans">{{ $opts['ack_title'] }}</h1>
        <ul style="margin:0;padding-left:18px;">
            @foreach (preg_split('/\R+/', $opts['acknowledgements']) ?: [] as $line)
                @if (trim($line) !== '')<li style="margin:0 0 4px;">{{ trim($line) }}</li>@endif
            @endforeach
        </ul>
    </div>
@endif

{{-- ===================== AVANT-PROPOS ===================== --}}
@if ($opts['foreword_text'] !== '')
    <div class="page-break">
        <h1 class="part sans">{{ $opts['foreword_title'] }}</h1>
        @foreach (preg_split('/\R{2,}/', $opts['foreword_text']) ?: [] as $para)
            @if (trim($para) !== '')<p style="text-align:justify;">{{ trim($para) }}</p>@endif
        @endforeach
        @if ($opts['signature'] !== '')<p class="muted" style="text-align:right;font-style:italic;margin-top:18px;">{{ $opts['signature'] }}</p>@endif
    </div>
@endif

{{-- ================= MOTS SIGNÉS (bienvenue, président) ================= --}}
@foreach ([['welcome_title', 'welcome_text', 'welcome_signature'], ['president_title', 'president_text', 'president_signature']] as $msg)
    @if ($opts[$msg[1]] !== '')
        <div class="page-break">
            <h1 class="part sans">{{ $opts[$msg[0]] }}</h1>
            @foreach (preg_split('/\R{2,}/', $opts[$msg[1]]) ?: [] as $para)
                @if (trim($para) !== '')<p style="text-align:justify;">{{ trim($para) }}</p>@endif
            @endforeach
            @if ($opts[$msg[2]] !== '')<p class="muted" style="text-align:right;font-style:italic;margin-top:18px;">{{ $opts[$msg[2]] }}</p>@endif
        </div>
    @endif
@endforeach

{{-- ========== DIFFÉRENTES COMMISSIONS ET PROGRAMMES DU COLLOQUE ========== --}}
@php
    $committeeList = collect($committeeRoles)->filter(fn ($lbl, $r) => ($committees[$r] ?? collect())->isNotEmpty());
    $synDays = collect($programSynthesis['days'] ?? [])->filter(fn ($d) => trim((string) ($d['heading'] ?? '')) !== '' || trim((string) ($d['date'] ?? '')) !== '' || collect($d['rows'] ?? [])->isNotEmpty());
    $hmp = fn ($t) => trim((string) $t) === '' ? '' : str_replace(':', 'h', substr((string) $t, 0, 5));
    // Numéro de la rubrique « Communications en ateliers » dans la section A
    // (comités présents + programme synthétique), pour « 5. COMMUNICATIONS… ».
    $ateliersNo = $committeeList->count() + ($synDays->isNotEmpty() ? 1 : 0) + 1;
    $hColor = $isGen ? $palette['black'] : $primary;
    $isCoord = fn ($q) => (bool) preg_match('/president|vice|rapporteur|coordinat|secretaire|tresorier/i', \Illuminate\Support\Str::ascii((string) $q));
@endphp
@if ($committeeList->isNotEmpty() || $synDays->isNotEmpty())
    <div class="page-break">
        @if ($isGen)
            <p class="sans" style="font-weight:bold;font-size:14pt;color:{{ $palette['black'] }};margin:0 0 14px;">A) {{ $commissionsTitle }}</p>
        @else
            <h1 class="part sans">{{ $commissionsTitle ?? 'Différentes commissions et programmes du colloque' }}</h1>
        @endif
        @php $num = 0; @endphp
        @foreach ($committeeList as $role => $roleLabel)
            @php $num++; $list = $committees[$role] ?? collect(); @endphp
            <h3 class="sans" style="color:{{ $hColor }};font-size:14pt;margin:16px 0 6px;">{{ $num }}. {{ $isGen ? \Illuminate\Support\Str::upper($roleLabel) : $roleLabel }}</h3>
            @if ($isGen && $role === 'organisation')
                {{-- Comité d'organisation en TABLEAU (modèle « Document général du colloque »),
                     scindé COORDINATION / MEMBRES comme le document de référence. --}}
                @php
                    $coord = $list->filter(fn ($m) => $isCoord($m->qualite));
                    $membres = $list->reject(fn ($m) => $isCoord($m->qualite));
                    $grouped = $coord->isNotEmpty() && $membres->isNotEmpty();
                    $ri = 0;
                @endphp
                <table class="prog">
                    <tr><th style="width:6%;">N°</th><th style="width:30%;">NOM &amp; PRÉNOM(S)</th><th style="width:14%;">GRADE</th><th style="width:30%;">CONTACTS</th><th style="width:20%;">QUALITÉ</th></tr>
                    @foreach (($grouped ? ['COORDINATION' => $coord, 'MEMBRES' => $membres] : ['' => $list]) as $grpLabel => $grpList)
                        @if ($grpLabel !== '')<tr class="grp"><td colspan="5">{{ $grpLabel }}</td></tr>@endif
                        @foreach ($grpList as $m)
                            @php $ri++; $contacts = array_filter([trim((string) $m->phone), trim((string) $m->email)]); @endphp
                            <tr>
                                <td style="text-align:center;">{{ $ri }}</td>
                                <td>{{ trim((string) $m->person_name) ?: '-' }}</td>
                                <td style="text-align:center;">{{ trim((string) $m->person_title) }}</td>
                                <td>{{ implode('  ', $contacts) }}</td>
                                <td>{{ trim((string) $m->qualite) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </table>
            @else
                @foreach ($list as $m)
                    @php $lead = trim(trim((string) $m->person_title).' '.trim((string) $m->person_name)); $tail = trim((string) $m->institution); @endphp
                    <p style="margin:0 0 3px;text-align:justify;">{{ $tail !== '' ? $lead.', '.$tail : $lead }}</p>
                @endforeach
            @endif
        @endforeach
        @if ($synDays->isNotEmpty())
            @php $num++; @endphp
            <h3 class="sans" style="color:{{ $hColor }};font-size:14pt;margin:18px 0 6px;">{{ $num }}. {{ $isGen ? 'PROGRAMME SYNTHÉTIQUE DU COLLOQUE' : 'Programme synthétique du colloque' }}</h3>
            @foreach ($synDays as $day)
                @php
                    $dayHeading = trim((string) ($day['heading'] ?? ''));
                    $dayDate = trim((string) ($day['date'] ?? ''));
                    $dLabel = trim(($dayDate !== '' ? ucfirst(\Illuminate\Support\Carbon::parse($dayDate)->translatedFormat('l d/m/Y')) : '').($dayHeading !== '' ? '  '.$dayHeading : ''));
                @endphp
                @if ($dLabel !== '')<p style="font-weight:bold;color:{{ $hColor }};margin:10px 0 2px;">{{ $dLabel }}</p>@endif
                @foreach ($day['rows'] ?? [] as $row)
                    @php
                        $rs = $hmp($row['start'] ?? ''); $re = $hmp($row['end'] ?? '');
                        $rtime = $re !== '' ? $rs.' - '.$re : $rs;
                        $rlabel = trim((string) ($row['label'] ?? ''));
                        $rtext = $rtime !== '' ? trim($rtime.' : '.$rlabel) : $rlabel;
                    @endphp
                    @if ($rtext !== '')<p style="margin:0 0 2px;">{{ $rtext }}</p>@endif
                @endforeach
            @endforeach
        @endif
    </div>
@endif

{{-- ============ PROGRAMME (par jour) / COMMUNICATIONS EN ATELIERS (par axe) ============ --}}
@if (($programmeGroups ?? collect())->isNotEmpty())
    <div class="page-break">
        @if ($isGen)
            <p class="sans" style="text-align:center;font-weight:bold;font-size:14pt;color:{{ $palette['black'] }};margin:0 0 14px;">{{ $ateliersNo }}. COMMUNICATIONS EN ATELIERS</p>
        @else
            <h1 class="part sans">{{ $programmeTitle ?? 'Programme' }}</h1>
        @endif
        @foreach ($programmeGroups as $grp)
            @if (! empty($grp['heading']))
                {{-- keepTitle : le titre d'atelier reste avec le DÉBUT de son premier tableau
                     (le titre d'atelier + le bandeau + l'en-tête + la 1re ligne ne se séparent
                     pas) ; le tableau démarre juste après le titre et peut se couper plus bas,
                     dans les DONNÉES, l'en-tête se répétant à chaque page. --}}
                <p class="sans @if ($isGen)atelier-head @endif keep-with-next" style="text-align:center;font-weight:bold;@if (! $isGen)color:{{ $primary }};@endif font-size:14pt;margin:18px 0 8px;">{{ $grp['heading'] }}</p>
            @endif
            @if ($isGen)
                {{-- Modèle « Document général » : un atelier se déroule SÉANCE PAR SÉANCE.
                     Chaque séance porte son horaire + salle (bandeau), son modérateur et son
                     rapporteur, puis le tableau « Horaire | Code | Auteur(s) | Titre ». Le
                     bandeau de jour n'apparaît qu'au changement de jour. Les bandeaux + l'en-tête
                     + la 1re ligne vivent dans un même <tbody> insécable (`prog-start`) ; les
                     lignes suivantes se coupent librement d'une page à l'autre. --}}
                @php $lastDay = null; @endphp
                @foreach ($grp['sessions'] as $sess)
                    @php
                        $sline = 'Session';
                        if ($sess['time'] !== '') { $sline .= ' : '.$sess['time']; }
                        if ($sess['room'] !== '') { $sline .= ($sess['time'] !== '' ? ' - ' : ' : ').$sess['room']; }
                    @endphp
                    <table class="prog">
                        <tbody class="prog-start">
                            @if ($sess['day'] !== $lastDay)
                                @php $lastDay = $sess['day']; @endphp
                                <tr><td class="day-banner" colspan="4">{{ $dayLabel($sess['day']) }}</td></tr>
                            @endif
                            <tr><td class="session-banner" colspan="4">{{ $sline }}</td></tr>
                            <tr><td class="session-roles" colspan="4"><b>Modérateur :</b> {{ $sess['moderator'] !== '' ? $sess['moderator'] : '-' }} &nbsp;&nbsp;&nbsp; <b>Rapporteur :</b> {{ $sess['rapporteur'] !== '' ? $sess['rapporteur'] : '-' }}</td></tr>
                            <tr><th style="width:16%;">Horaire</th><th style="width:12%;">Code</th><th style="width:25%;">Auteur(s)</th><th style="width:47%;">Titre de la communication</th></tr>
                            @foreach ($sess['slots'] as $sl)
                                @if ($loop->index === 1)</tbody><tbody>@endif
                                @php
                                    $code = trim((string) $sl->submission?->ref_code);
                                    $title = trim((string) ($sl->submission?->title ?: $sl->session_label));
                                    $authorsArr = $sl->submission?->authors ?? [];
                                    $ci = collect($authorsArr)->search(fn ($a) => (bool) ($a['corresponding'] ?? false));
                                    $ci = $ci === false ? 0 : (int) $ci;
                                    $ca = $authorsArr[$ci] ?? null;
                                    $author = $ca && $sl->submission
                                        ? \App\Documents\LivretDocx::authorCase(\App\Support\EventNameFormat::submissionAuthor($event, $sl->submission, $ca, $ci), $isGen)
                                        : '';
                                @endphp
                                <tr>
                                    <td style="white-space:nowrap;">{{ $hm($sl->starts_at) ?: '-' }}@if ($sl->ends_at) - {{ $hm($sl->ends_at) }}@endif</td>
                                    <td style="font-weight:bold;white-space:nowrap;">{{ $code ?: '-' }}</td>
                                    <td>{{ $author !== '' ? $author : '-' }}</td>
                                    <td>{{ $title !== '' ? \App\Documents\LivretDocx::bindPunctuation($title) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @else
                @foreach ($grp['days'] as $day => $daySlots)
                    <p class="sans" style="font-weight:bold;color:{{ $primary }};margin:12px 0 4px;">{{ $dayLabel($day) }}</p>
                    <table class="prog">
                        <tr><th style="width:16%;">Horaire</th><th style="width:58%;">Communication</th><th style="width:26%;">Salle / Session</th></tr>
                        @foreach ($daySlots as $sl)
                            @php
                                $title = trim((string) ($sl->submission?->title ?: $sl->session_label));
                                $auth = collect($sl->submission?->authors ?? [])->map(function ($a, $index) use ($isGen, $event, $sl) {
                                    // Même casse que le .docx (source unique).
                                    $name = \App\Documents\LivretDocx::authorCase(\App\Support\EventNameFormat::submissionAuthor($event, $sl->submission, $a, (int) $index), $isGen);
                                    $aff = trim((string) ($a['affiliation'] ?? ''));
                                    return $name === '' ? '' : ($aff !== '' ? $name.' ('.$aff.')' : $name);
                                })->filter()->implode('; ');
                                $room = trim((string) ($sl->room ?: $sl->session?->room));
                                $sessLabel = trim((string) ($sl->session?->label ?: $sl->session_label));
                            @endphp
                            <tr>
                                <td style="white-space:nowrap;">{{ $hm($sl->starts_at) ?: '-' }}@if ($sl->ends_at) - {{ $hm($sl->ends_at) }}@endif</td>
                                <td>{{ $title ?: '-' }}@if ($auth !== '')<br><span class="muted" style="font-style:italic;font-size:8.5pt;">{{ $auth }}</span>@endif</td>
                                <td>{{ $room }}@if ($sessLabel !== '' && $sessLabel !== $title)<br><span class="muted" style="font-size:8.5pt;">{{ $sessLabel }}</span>@endif</td>
                            </tr>
                        @endforeach
                    </table>
                @endforeach
            @endif
        @endforeach
    </div>
@endif

{{-- ===================== RÉSUMÉS ===================== --}}
<div class="page-break">
    @if ($isGen)
        <p class="sans center" style="font-weight:bold;font-size:16pt;color:{{ $palette['black'] }};margin:0 0 14px;">B) Résumés des communications</p>
    @else
        <h1 class="part sans">Résumés des communications</h1>
    @endif
    @php $n = 0; $axisNo = 0; @endphp
    @forelse ($groups as $track => $submissions)
        @php $axisNo++; $axisClean = \App\Documents\LivretDocx::ateliersAxisLabel((string) ($track ?: 'Autres')); @endphp
        @foreach ($submissions as $s)
            @php
                $n++;
                $ref = trim((string) $s->ref_code);
                $people = collect($s->authors ?? [])->map(fn ($a, $index) => [
                    // Casse SELON LE STYLE, via la source unique partagée avec le .docx
                    // (« NOM Prénom » tel quel pour general, MAJUSCULES pour default/fss).
                    'name' => \App\Documents\LivretDocx::authorCase(\App\Support\EventNameFormat::submissionAuthor($event, $s, $a, (int) $index), $isGen),
                    'aff' => trim((string) ($a['affiliation'] ?? '')),
                    'email' => trim((string) ($a['email'] ?? '')),
                    'phone' => trim((string) ($a['phone'] ?? '')),
                    'corr' => (bool) ($a['corresponding'] ?? false),
                ])->filter(fn ($p) => $p['name'] !== '')->values();
                // Numérotation des affiliations distinctes de ce résumé.
                $affs = [];
                foreach ($people as $p) {
                    if ($p['aff'] !== '' && ! in_array($p['aff'], $affs, true)) { $affs[] = $p['aff']; }
                }
                $corr = $people->firstWhere('corr', true);
                // Ligne des auteurs construite en PHP. ⚠️ Chaque auteur (nom + exposant
                // d'affiliation + astérisque de correspondant) est enveloppé dans un
                // <span white-space:nowrap> : sans cela l'exposant « 4 » se détachait du
                // nom et retombait seul à la ligne. La coupure ne se fait qu'aux virgules.
                $authorsHtml = '';
                foreach ($people as $i => $p) {
                    if ($i > 0) { $authorsHtml .= ', '; }
                    $tok = e($p['name']);
                    if ($p['aff'] !== '') { $tok .= '<sup>'.(array_search($p['aff'], $affs, true) + 1).'</sup>'; }
                    if ($p['corr']) { $tok .= '<sup>*</sup>'; }
                    $authorsHtml .= '<span style="white-space:nowrap;">'.$tok.'</span>';
                }
            @endphp
            <div class="abs-entry">
                @if ($loop->first)
                    {{-- L'axe et le premier en-tête forment un seul bloc : si ce bloc
                         ne tient plus, dompdf le reporte entier sur la page suivante. --}}
                    <div class="axis-start">
                        @if ($isGen)
                            <h2 class="axis sans" style="text-align:center;font-size:14pt;">{{ $axisNo }}- {{ \Illuminate\Support\Str::upper($axisClean !== '' ? $axisClean : (string) ($track ?: 'Autres')) }}</h2>
                        @else
                            <h2 class="axis sans" style="text-align:{{ $opts['align_axis'] }};">{{ \Illuminate\Support\Str::upper((string) ($track ?: 'Autres')) }}</h2>
                        @endif
                @endif
                {{-- Bloc insécable : réf. + titre + auteurs + affiliations + correspondant
                     ne se coupent JAMAIS entre deux pages (pas de code seul en bas de page). --}}
                <div class="abs-head">
                    @if ($ref !== '')<p class="ref">{{ $ref }}</p>@endif
                    <p class="abs-title sans" style="text-align:{{ $isGen ? 'center' : $opts['align_titles'] }};">{{ $opts['number'] ? $n.'. ' : '' }}{{ $isGen ? \App\Documents\LivretDocx::bindPunctuation((string) $s->title) : $s->title }}</p>
                    @if ($people->isNotEmpty())
                        <p class="authors" style="text-align:{{ $isGen ? 'center' : $opts['align_authors'] }};">{!! $authorsHtml !!}</p>
                        @foreach ($affs as $i => $aff)
                            <p class="aff"><sup>{{ $i + 1 }}</sup> {{ $aff }}</p>
                        @endforeach
                        @php
                            $cbits = [];
                            if ($corr && $corr['email'] !== '') { $cbits[] = 'Email : '.$corr['email']; }
                            if ($corr && $corr['phone'] !== '') { $cbits[] = 'Tél : '.$corr['phone']; }
                        @endphp
                        @if ($corr && $cbits !== [])
                            <p class="corr">*Auteur correspondant : {{ $corr['name'] }} &nbsp; {{ implode('  ', $cbits) }}</p>
                        @endif
                    @endif
                </div>
                @if ($loop->first)</div>@endif
                @php $body = trim(\App\Support\Html::render($s->abstract)); @endphp
                @if ($body !== '')
                    {{-- Le libellé reste avec le début du texte ; le résumé peut se poursuivre
                         sur la page suivante sans laisser un grand espace vide. --}}
                    <div class="abs-resume">
                        <p class="lbl sans">{{ $isGen ? 'Résumé' : 'RÉSUMÉ :' }}</p>
                        <div class="abs-body ukc-rte">{!! $body !!}</div>
                    </div>
                @endif
                @if ($kw = \App\Support\SubmissionKeywords::join($s->keywords, (new \App\Support\SubmissionFormConfig($s->event))->keywordSeparator()))
                    <p class="kw"><b>Mots clés :</b> <span class="muted" style="font-style:italic;">{{ $kw }}</span></p>
                @endif
            </div>
        @endforeach
    @empty
        <p class="muted" style="font-style:italic;">Aucune communication acceptée pour le moment.</p>
    @endforelse
</div>

{{-- ===================== INDEX DES AUTEURS ===================== --}}
@if ($opts['author_index'] && ! empty($authorIndex))
    <div class="page-break">
        <h1 class="part sans">Index des auteurs</h1>
        @foreach ($authorIndex as $name => $refs)
            <p class="idx"><b>{{ $name }}</b>&nbsp; <span class="muted">{{ $refs }}</span></p>
        @endforeach
    </div>
@endif
</body>
</html>
