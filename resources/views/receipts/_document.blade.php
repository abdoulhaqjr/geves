{{-- ⚠️⚠️ **UN SEUL DOCUMENT, DEUX SORTIES.** Cette vue est le reçu : elle sert la
     page imprimable, l'aperçu verrouillé ET le PDF téléchargé. Ils ont vécu dans
     deux fichiers jumeaux, qui ont divergé - l'un portait la devise, l'autre pas.
     ⚠️ Donc tout est écrit en **tableaux et styles en ligne**, et en couleurs
     littérales : dompdf ne connaît ni flexbox, ni grille, ni variable CSS. Ce qui
     s'y rend se rend partout ; l'inverse n'est pas vrai. --}}
@php
    $pdf = $pdf ?? false;

    // ⚠️ dompdf ne va chercher aucune image distante : en PDF, le logo doit être
    // un CHEMIN DE FICHIER. (Un logo téléversé passe par /storage, d'où le
    // `storage:link` rappelé dans le guide de déploiement.)
    $logo = \App\Support\Branding::mark();
    $logoSrc = $pdf ? public_path((string) parse_url($logo, PHP_URL_PATH)) : $logo;

    $gw = $payment->gatewayDetail();
    $somme = \App\Support\Money::format($payment->amount, $payment->currency);
    $lettres = \App\Support\MontantEnLettres::avecDevise($payment->amount, $payment->currency);

    $objet = $payment->flowLabel();
    $precision = $payment->flowDetail() ?: $payment->participant?->title;

    $bleu = '#223ea4';
    $encre = '#0f172a';
    $gris = '#64748b';
    $filet = '#e2e8f0';
@endphp

<div style="background:#ffffff;color:{{ $encre }};font-size:13px;line-height:1.5;
            border:1px solid {{ $filet }};max-width:760px;margin:0 auto;">

    {{-- ⚠️ Les trois couleurs de l'université vont BORD À BORD : le rembourrage
         vit dans la boîte du dessous, sinon le bandeau flotte au milieu du blanc.
         ⚠️ Et `font-size:0` sur les cellules - une cellule avec un espace
         insécable fait la hauteur de SA LIGNE DE TEXTE, pas celle qu'on lui
         donne : le filet de 4px en rendait dix. --}}
    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
        <tr style="font-size:0;line-height:0;">
            <td style="height:5px;font-size:0;line-height:0;background:{{ $bleu }};">&nbsp;</td>
            <td style="height:5px;font-size:0;line-height:0;background:#ffd700;">&nbsp;</td>
            <td style="height:5px;font-size:0;line-height:0;background:#009933;">&nbsp;</td>
        </tr>
    </table>

    <div style="padding:28px 34px 24px;">

    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top;width:62%;">
                <table cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        @if ($logoSrc)
                            <td style="vertical-align:middle;padding-right:12px;">
                                <img src="{{ $logoSrc }}" alt="" style="height:46px;">
                            </td>
                        @endif
                        <td style="vertical-align:middle;">
                            <div style="font-size:15px;font-weight:bold;color:{{ $bleu }};letter-spacing:.02em;">
                                {{ __('Université de Kara') }}
                            </div>
                            <div style="font-size:11.5px;color:{{ $gris }};">
                                {{ __('Direction de la Recherche et de l’Innovation') }}
                            </div>
                            @if ($payment->event?->series?->name)
                                <div style="font-size:11.5px;color:{{ $gris }};">{{ $payment->event->series->name }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align:top;text-align:right;">
                <div style="font-size:10.5px;color:{{ $gris }};text-transform:uppercase;letter-spacing:.08em;">
                    {{ __('Reçu de paiement') }}
                </div>
                <div style="font-size:17px;font-weight:bold;color:{{ $encre }};">{{ $payment->receipt_no ?: '-' }}</div>
                <div style="font-size:11.5px;color:{{ $gris }};">
                    {{ __('Émis le :date', ['date' => ($payment->paid_at ?? now())->translatedFormat('j F Y')]) }}
                </div>
            </td>
        </tr>
    </table>

    <div style="border-top:1px solid {{ $filet }};margin-top:20px;"></div>

    {{-- ⚠️ Qui a payé, et au titre de quoi : les deux blocs d'un reçu. --}}
    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-top:20px;">
        <tr>
            <td style="vertical-align:top;width:50%;padding-right:18px;">
                <div style="font-size:10.5px;color:{{ $gris }};text-transform:uppercase;letter-spacing:.06em;">
                    {{ __('Reçu de') }}
                </div>
                <div style="font-size:14px;font-weight:bold;margin-top:4px;">
                    {{ $payment->participant?->full_name ?: '-' }}
                </div>
                @if ($payment->participant?->email)
                    <div style="font-size:12px;color:{{ $gris }};">{{ $payment->participant->email }}</div>
                @endif
                @if ($precision)
                    <div style="font-size:12px;color:{{ $gris }};">{{ $precision }}</div>
                @endif
            </td>
            <td style="vertical-align:top;width:50%;">
                <div style="font-size:10.5px;color:{{ $gris }};text-transform:uppercase;letter-spacing:.06em;">
                    {{ __('Au titre de') }}
                </div>
                <div style="font-size:14px;font-weight:bold;margin-top:4px;">{{ $payment->event?->title ?: '-' }}</div>
                @if ($payment->event?->dateRangeLabel())
                    <div style="font-size:12px;color:{{ $gris }};">{{ $payment->event->dateRangeLabel() }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ⚠️ Le détail est une LIGNE DE FACTURE, pas une suite de « champ : valeur » :
         c'est la forme que tout service comptable attend, et elle laisse la place
         à une seconde ligne le jour où un reçu en portera deux. --}}
    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-top:22px;">
        <tr>
            <td style="background:#f8fafc;border:1px solid {{ $filet }};padding:9px 12px;
                       font-size:10.5px;color:{{ $gris }};text-transform:uppercase;letter-spacing:.06em;">
                {{ __('Désignation') }}
            </td>
            <td style="background:#f8fafc;border:1px solid {{ $filet }};padding:9px 12px;text-align:right;
                       font-size:10.5px;color:{{ $gris }};text-transform:uppercase;letter-spacing:.06em;">
                {{ __('Montant') }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid {{ $filet }};padding:12px;">
                <div style="font-weight:bold;">{{ $objet }}</div>
                @if ($precision)
                    <div style="font-size:12px;color:{{ $gris }};">{{ $precision }}</div>
                @endif
            </td>
            <td style="border:1px solid {{ $filet }};padding:12px;text-align:right;white-space:nowrap;">
                {{ $somme }}
            </td>
        </tr>
        <tr>
            <td style="border:1px solid {{ $filet }};padding:12px;font-weight:bold;">{{ __('Total réglé') }}</td>
            <td style="border:1px solid {{ $filet }};padding:12px;text-align:right;white-space:nowrap;
                       font-size:19px;font-weight:bold;color:{{ $bleu }};">
                {{ $somme }}
            </td>
        </tr>
    </table>

    {{-- ⚠️⚠️ **LA SOMME EN TOUTES LETTRES FIGE LE REÇU.** Un chiffre se retouche
         d'un trait de stylo ; la ligne en lettres, non. C'est pour cela que tout
         reçu officiel la porte, et c'est elle qui fait foi en cas de désaccord. --}}
    <div style="margin-top:14px;padding:11px 13px;background:#f8fafc;border:1px solid {{ $filet }};font-size:12.5px;">
        {{ __('Arrêté le présent reçu à la somme de') }}
        <span style="font-weight:bold;">{{ $lettres }}</span>.
    </div>

    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-top:20px;font-size:12px;">
        <tr>
            <td style="vertical-align:top;width:50%;padding-right:18px;">
                <div style="color:{{ $gris }};">{{ __('Moyen de paiement') }}</div>
                <div style="font-weight:bold;">{{ $payment->methodLabel() }}</div>
                @if (($gw['operator_label'] ?? '') !== '')
                    <div style="color:{{ $gris }};">{{ $gw['operator_label'] }}</div>
                @endif
            </td>
            <td style="vertical-align:top;width:50%;">
                <div style="color:{{ $gris }};">{{ __('Date du paiement') }}</div>
                <div style="font-weight:bold;">{{ $payment->paid_at?->translatedFormat('j F Y') ?: '-' }}</div>
            </td>
        </tr>
        {{-- ⚠️ La référence de l'opérateur est celle que le payeur lit sur SON
             relevé : c'est par elle qu'un règlement contesté se retrouve. --}}
        @if (($gw['operator_ref'] ?? '') !== '' || $payment->reference)
            <tr>
                <td style="vertical-align:top;padding-top:12px;padding-right:18px;">
                    <div style="color:{{ $gris }};">{{ __('Référence du paiement') }}</div>
                    <div style="font-weight:bold;">{{ $payment->reference ?: '-' }}</div>
                </td>
                <td style="vertical-align:top;padding-top:12px;">
                    @if (($gw['operator_ref'] ?? '') !== '')
                        <div style="color:{{ $gris }};">{{ __('Référence opérateur') }}</div>
                        <div style="font-weight:bold;">{{ $gw['operator_ref'] }}</div>
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <div style="border-top:1px solid {{ $filet }};margin-top:22px;"></div>

    <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-top:12px;">
        <tr>
            <td style="vertical-align:top;font-size:10.5px;color:{{ $gris }};line-height:1.6;">
                {{ __('Document établi par :app et généré automatiquement.', ['app' => config('app.name')]) }}<br>
                {{ __('Il vaut reçu du règlement ci-dessus et ne nécessite pas de signature.') }}
            </td>
            <td style="vertical-align:top;text-align:right;font-size:10.5px;color:{{ $gris }};">
                {{ $payment->receipt_no ?: '' }}
            </td>
        </tr>
    </table>
    </div>
</div>
