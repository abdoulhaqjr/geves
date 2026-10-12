<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('documents._fonts', ['pdf' => true])
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: 'Jost', DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 14pt; margin: 0 0 2mm; }
        .meta { font-size: 9pt; color: #555; margin-bottom: 6mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: .4pt solid #999; padding: 2.4mm 2mm; vertical-align: top; }
        th { background: #f1f3f6; font-size: 8.5pt; text-align: left; text-transform: uppercase; letter-spacing: .3pt; }
        /* ⚠️ L'en-tête se répète sur chaque page : une liste d'émargement se
           signe sur plusieurs feuilles, et la deuxième page sans titre de
           colonnes ne se lit plus. */
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .ref { font-size: 8pt; color: #555; }
        .sign { height: 11mm; }
        .pied { margin-top: 6mm; font-size: 8.5pt; color: #555; }
    </style>
</head>
<body>
    <h1>Liste d’émargement - Attestations</h1>
    <div class="meta">
        {{ $event->series?->name }} - {{ $event->title ?: $event->year }} -
        {{ $typeLabel }} - {{ $attestations->count() }} bénéficiaire(s) -
        éditée le {{ now()->format('d/m/Y à H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:7mm;">#</th>
                <th>Bénéficiaire</th>
                <th style="width:34mm;">Référence</th>
                <th style="width:22mm;">Remise</th>
                <th style="width:46mm;">Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attestations as $i => $a)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $a->recipient_name ?: $a->participant?->full_name }}</strong>
                        <div class="ref">{{ $a->recipient_email ?: $a->participant?->email }}</div>
                        @if ($a->meta['communication_title'] ?? null)
                            <div class="ref">{{ \Illuminate\Support\Str::limit($a->meta['communication_title'], 70) }}</div>
                        @endif
                    </td>
                    <td class="ref">{{ $a->uuid }}</td>
                    {{-- Déjà remise : la case porte la date, pour ne pas la faire
                         signer deux fois. --}}
                    <td class="ref">{{ $a->delivered_at?->format('d/m/Y') ?? '' }}</td>
                    <td class="sign"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pied">
        Fait à ……………………………… le ……… / ……… / ………  -  Le secrétariat : ………………………………………
    </div>
</body>
</html>
