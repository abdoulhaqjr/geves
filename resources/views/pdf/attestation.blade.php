<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; color: #1f2937; }
        .sheet { padding: 48px; position: relative; }
        .bar { height: 8px; width: 100%; }
        .bar td { padding: 0; }
        .muted { color: #6b7280; }
        .blue { color: #223ea4; }
        h1 { font-size: 30px; font-weight: bold; color: #111827; margin: 26px 0 6px; }
    </style>
</head>
<body>
    <table class="bar" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="background:#223ea4;">&nbsp;</td>
            <td style="background:#ffd700;">&nbsp;</td>
            <td style="background:#009933;">&nbsp;</td>
        </tr>
    </table>

    <div class="sheet">
        <div style="text-align:center;">
            <div style="font-weight:bold;color:#223ea4;font-size:17px;">{{ $attestation->edition?->colloque?->name }}</div>
            <div style="font-size:12px;" class="muted">Université de Kara</div>

            <h1>{{ __('Attestation de :type', ['type' => mb_strtolower(__($attestation->typeLabel()))]) }}</h1>
            <div style="width:80px;height:3px;background:#ffd700;margin:0 auto 26px;"></div>

            <p style="font-size:14px;" class="muted">{{ __('Décernée à') }}</p>
            <p style="font-size:24px;font-weight:bold;color:#223ea4;margin:6px 0 22px;">{{ $attestation->recipient_name }}</p>

            <p style="font-size:14px;line-height:1.7;">
                {{ __('pour sa :type à', ['type' => mb_strtolower(__($attestation->typeLabel()))]) }}
                <strong>{{ $attestation->edition?->title }}</strong>@if ($attestation->edition?->location), {{ $attestation->edition->location }}@endif.
            </p>

            @php($communicationTitle = data_get($attestation->meta, 'communication_title'))
            @if ($attestation->type === 'communication' && $communicationTitle)
                @php($communicationRef = data_get($attestation->meta, 'communication_ref'))
                <p style="font-size:14px;line-height:1.7;margin-top:10px;">
                    {{ __('pour la communication') }}
                    <strong>« {{ $communicationTitle }} »</strong>@if ($communicationRef) <span class="muted">({{ __('réf.') }} {{ $communicationRef }})</span>@endif.
                </p>
            @endif

            @php($formationTitle = data_get($attestation->meta, 'formation_title'))
            @if (in_array($attestation->type, ['formation', 'formation_animation'], true) && $formationTitle)
                <p style="font-size:14px;line-height:1.7;margin-top:10px;">
                    {{ $attestation->type === 'formation_animation' ? __('pour l’animation de la formation') : __('pour la formation') }}
                    <strong>« {{ $formationTitle }} »</strong>.
                </p>
            @endif
        </div>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:48px;">
            <tr>
                <td style="vertical-align:bottom;font-size:12px;" class="muted">
                    <div>{{ __('Délivrée le :date', ['date' => $attestation->issued_at?->format('d/m/Y')]) }}</div>
                    <div style="margin-top:6px;font-size:10px;">{{ __('Réf.') }} {{ $attestation->uuid }}</div>
                </td>
                <td style="text-align:right;width:150px;">
                    <div style="width:120px;height:120px;display:inline-block;">{!! $qr !!}</div>
                    <div style="font-size:9px;color:#9ca3af;margin-top:2px;">{{ __('Scannez pour vérifier') }}</div>
                </td>
            </tr>
        </table>

        <p style="text-align:center;font-size:10px;color:#9ca3af;margin-top:28px;">{{ __('Vérification :') }} {{ $verifyUrl }}</p>
    </div>
</body>
</html>
