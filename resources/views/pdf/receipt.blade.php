<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 13px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        .bar td { height: 6px; padding: 0; }
    </style>
</head>
<body>
    <table class="bar" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background:#223ea4;">&nbsp;</td>
            <td style="background:#ffd700;">&nbsp;</td>
            <td style="background:#009933;">&nbsp;</td>
        </tr>
    </table>

    <table style="margin-top:20px;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-weight:bold;color:#223ea4;font-size:16px;">{{ $payment->edition?->colloque?->name }}</div>
                <div style="font-size:12px;" class="muted">Université de Kara</div>
            </td>
            <td style="text-align:right;vertical-align:top;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;" class="muted">Reçu de paiement</div>
                <div style="font-weight:bold;font-size:16px;">{{ $payment->receipt_no }}</div>
            </td>
        </tr>
    </table>

    <hr style="border:none;border-top:1px solid #e5e7eb;margin:22px 0;">

    <table>
        <tr><td class="muted" style="padding:7px 0;">Reçu de</td><td style="padding:7px 0;text-align:right;font-weight:bold;">{{ $payment->participant?->full_name }}</td></tr>
        <tr><td class="muted" style="padding:7px 0;">E-mail</td><td style="padding:7px 0;text-align:right;">{{ $payment->participant?->email }}</td></tr>
        <tr><td class="muted" style="padding:7px 0;">Édition</td><td style="padding:7px 0;text-align:right;">{{ $payment->edition?->title }}</td></tr>
        <tr><td class="muted" style="padding:7px 0;">Moyen de paiement</td><td style="padding:7px 0;text-align:right;">{{ $payment->methodLabel() }}</td></tr>
        <tr><td class="muted" style="padding:7px 0;">Date</td><td style="padding:7px 0;text-align:right;">{{ $payment->paid_at?->format('d/m/Y') }}</td></tr>
    </table>

    <table style="margin-top:22px;background:#f3f4f6;">
        <tr>
            <td style="padding:16px 18px;font-weight:bold;">Montant réglé</td>
            <td style="padding:16px 18px;text-align:right;font-size:22px;font-weight:bold;color:#223ea4;">
                {{ number_format((float) $payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
            </td>
        </tr>
    </table>

    <p style="text-align:center;font-size:10px;color:#9ca3af;margin-top:24px;">
        Reçu généré le {{ now()->format('d/m/Y') }} — {{ $payment->edition?->colloque?->name }}.
    </p>
</body>
</html>
