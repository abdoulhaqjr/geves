@php
    /** @var \App\Models\Edition $edition */
    $primary = $opts['color_primary'];
    $muted = '#6b7280';
    $hm = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('H:i') : '';
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Livret des résumés - {{ $edition->title }}</title>
    <style>
        @page { margin: 2.2cm 2.2cm 2.4cm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Serif', serif; font-size: 11pt; line-height: 1.5; color: #1f2937; }
        h1, h2, h3, .sans { font-family: 'DejaVu Sans', sans-serif; }
        .footer { position: fixed; bottom: -1.3cm; left: 0; right: 0; text-align: center; font-size: 9pt; color: {{ $muted }}; }
        .footer:after { content: counter(page); }
        .page-break { page-break-before: always; }
        .center { text-align: center; }
        .muted { color: {{ $muted }}; }
        .banner { width: 100%; margin: 10px 0 18px; }
        .banner td { height: 8px; }
        h1.part { font-size: 16pt; color: {{ $primary }}; border-bottom: 2px solid #ffd700; padding-bottom: 4px; margin: 0 0 14px; }
        h2.axis { font-size: 13pt; color: {{ $primary }}; text-transform: uppercase; margin: 18px 0 8px; }
        .ref { font-size: 9pt; font-weight: bold; color: {{ $muted }}; text-align: center; margin: 14px 0 2px; }
        .abs-title { font-size: 12pt; font-weight: bold; color: {{ $primary }}; margin: 0 0 3px; }
        .authors { font-size: 9.5pt; color: {{ $muted }}; margin: 0 0 2px; }
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
    </style>
</head>
<body>
<div class="footer"></div>

{{-- ===================== PAGE DE GARDE ===================== --}}
<div style="padding-top: 90px;">
    @if ($name = trim((string) $edition->colloque?->name))
        <p class="center sans" style="font-weight:bold;color:{{ $primary }};font-size:12pt;margin:0 0 6px;">{{ \Illuminate\Support\Str::upper($name) }}</p>
    @endif
    <table class="banner"><tr>
        <td style="background:{{ $primary }};"></td>
        <td style="background:#ffd700;"></td>
        <td style="background:#009933;"></td>
    </tr></table>
    <h1 class="sans center" style="font-size:24pt;margin:30px 0 6px;color:#111827;">{{ $edition->title }}</h1>
    @if ($label = trim((string) $edition->edition_label))
        <p class="center muted" style="margin:0 0 6px;">{{ $label }}</p>
    @endif
    @if ($opts['show_theme'] && ($theme = trim((string) $edition->theme)) !== '')
        <p class="center" style="font-style:italic;color:{{ $primary }};">« {{ $theme }} »</p>
    @endif
    @if ($opts['show_dates'] && $edition->date_start)
        <p class="center muted" style="margin-top:8px;">
            {{ $edition->date_start->format('d/m/Y') }}@if ($edition->date_end) - {{ $edition->date_end->format('d/m/Y') }}@endif
            @if ($loc = trim((string) $edition->location)) &nbsp;·&nbsp; {{ $loc }} @endif
        </p>
    @endif
    <p class="center sans" style="font-weight:bold;color:{{ $primary }};font-size:13pt;margin-top:60px;">{{ $opts['cover_mention'] }}</p>
</div>

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

{{-- ========== DIFFÉRENTES COMMISSIONS ET PROGRAMMES DU COLLOQUE ========== --}}
@php
    $committeeList = collect($committeeRoles)->filter(fn ($lbl, $r) => ($committees[$r] ?? collect())->isNotEmpty());
    $synDays = collect($programSynthesis['days'] ?? [])->filter(fn ($d) => trim((string) ($d['heading'] ?? '')) !== '' || trim((string) ($d['date'] ?? '')) !== '' || collect($d['rows'] ?? [])->isNotEmpty());
    $hmp = fn ($t) => trim((string) $t) === '' ? '' : str_replace(':', 'h', substr((string) $t, 0, 5));
@endphp
@if ($committeeList->isNotEmpty() || $synDays->isNotEmpty())
    <div class="page-break">
        <h1 class="part sans">Différentes commissions et programmes du colloque</h1>
        @php $num = 0; @endphp
        @foreach ($committeeList as $role => $roleLabel)
            @php $num++; $list = $committees[$role] ?? collect(); @endphp
            <h3 class="sans" style="color:{{ $primary }};font-size:13pt;margin:16px 0 6px;">{{ $num }}. {{ $roleLabel }}</h3>
            @foreach ($list as $m)
                @php $lead = trim(trim((string) $m->person_title).' '.trim((string) $m->person_name)); $tail = trim((string) $m->institution); @endphp
                <p style="margin:0 0 3px;">{{ $tail !== '' ? $lead.', '.$tail : $lead }}</p>
            @endforeach
        @endforeach
        @if ($synDays->isNotEmpty())
            @php $num++; @endphp
            <h3 class="sans" style="color:{{ $primary }};font-size:13pt;margin:18px 0 6px;">{{ $num }}. Programme synthétique du colloque</h3>
            @foreach ($synDays as $day)
                @php
                    $dayHeading = trim((string) ($day['heading'] ?? ''));
                    $dayDate = trim((string) ($day['date'] ?? ''));
                    $dLabel = trim(($dayDate !== '' ? ucfirst(\Illuminate\Support\Carbon::parse($dayDate)->translatedFormat('l d/m/Y')) : '').($dayHeading !== '' ? '  '.$dayHeading : ''));
                @endphp
                @if ($dLabel !== '')<p style="font-weight:bold;color:{{ $primary }};margin:10px 0 2px;">{{ $dLabel }}</p>@endif
                @foreach ($day['rows'] ?? [] as $row)
                    @php
                        $rs = $hmp($row['start'] ?? ''); $re = $hmp($row['end'] ?? '');
                        $rtime = $re !== '' ? $rs.' – '.$re : $rs;
                        $rlabel = trim((string) ($row['label'] ?? ''));
                        $rtext = $rtime !== '' ? trim($rtime.' : '.$rlabel) : $rlabel;
                    @endphp
                    @if ($rtext !== '')<p style="margin:0 0 2px;">{{ $rtext }}</p>@endif
                @endforeach
            @endforeach
        @endif
    </div>
@endif

{{-- ===================== PROGRAMME ===================== --}}
@if ($programme->isNotEmpty())
    <div class="page-break">
        <h1 class="part sans">Programme</h1>
        @foreach ($programme as $day => $daySlots)
            <p class="sans" style="font-weight:bold;color:{{ $primary }};margin:12px 0 4px;">{{ $dayLabel($day) }}</p>
            <table class="prog">
                <tr><th style="width:16%;">Horaire</th><th style="width:58%;">Communication</th><th style="width:26%;">Salle / Session</th></tr>
                @foreach ($daySlots as $sl)
                    @php
                        $title = trim((string) ($sl->submission?->title ?: $sl->session_label));
                        $auth = collect($sl->submission?->authors ?? [])->map(function ($a) {
                            $name = mb_strtoupper(trim((string) ($a['name'] ?? '')), 'UTF-8');
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
    </div>
@endif

{{-- ===================== RÉSUMÉS ===================== --}}
<div class="page-break">
    <h1 class="part sans">Résumés des communications</h1>
    @php $n = 0; @endphp
    @forelse ($groups as $track => $submissions)
        <h2 class="axis sans" style="text-align:{{ $opts['align_axis'] }};">{{ \Illuminate\Support\Str::upper((string) ($track ?: 'Autres')) }}</h2>
        @foreach ($submissions as $s)
            @php
                $n++;
                $ref = trim((string) $s->ref_code);
                $people = collect($s->authors ?? [])->map(fn ($a) => [
                    'name' => mb_strtoupper(trim((string) ($a['name'] ?? '')), 'UTF-8'),
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
                // Ligne des auteurs construite en PHP (exposants collés, sans espace parasite).
                $authorsHtml = '';
                foreach ($people as $i => $p) {
                    if ($i > 0) { $authorsHtml .= ', '; }
                    $authorsHtml .= e($p['name']);
                    if ($p['aff'] !== '') { $authorsHtml .= '<sup>'.(array_search($p['aff'], $affs, true) + 1).'</sup>'; }
                    if ($p['corr']) { $authorsHtml .= '<sup>*</sup>'; }
                }
            @endphp
            <div style="margin-top:10px;">
                @if ($ref !== '')<p class="ref">{{ $ref }}</p>@endif
                <p class="abs-title sans" style="text-align:{{ $opts['align_titles'] }};">{{ $opts['number'] ? $n.'. ' : '' }}{{ $s->title }}</p>
                @if ($people->isNotEmpty())
                    <p class="authors" style="text-align:{{ $opts['align_authors'] }};">{!! $authorsHtml !!}</p>
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
                @php $body = trim(\App\Support\Html::render($s->abstract)); @endphp
                @if ($body !== '')
                    <p class="lbl sans">RÉSUMÉ :</p>
                    <div class="abs-body ukc-rte">{!! $body !!}</div>
                @endif
                @if ($kw = trim((string) $s->keywords))
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
