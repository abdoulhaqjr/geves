<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Préférences du compte participant (onglet « Préférences » de Mon compte, fidèle
 * au mockup compte.html) : langue, fuseau, format de date, thème et canaux de
 * communication. Stockées dans `accounts.meta['prefs']`.
 */
class PreferencesController extends Controller
{
    /** Valeurs par défaut des préférences. */
    public const DEFAULTS = [
        'lang' => 'fr',
        'tz' => 'Africa/Lome',
        'date_format' => 'JJ/MM/AAAA',
        'theme' => 'Clair',
        'comm_transactional' => true,
        'comm_sms' => true,
        'comm_newsletter' => false,
    ];

    public const LANGS = ['fr' => 'Français', 'en' => 'English'];

    public const TIMEZONES = [
        'Africa/Lome' => 'Afrique/Lomé (GMT)',
        'Africa/Abidjan' => 'Afrique/Abidjan (GMT)',
        'Europe/Paris' => 'Europe/Paris (CET)',
    ];

    public const DATE_FORMATS = ['JJ/MM/AAAA', 'AAAA-MM-JJ', 'MM/JJ/AAAA'];

    public const THEMES = ['Clair', 'Sombre (bientôt)', 'Système'];

    public function edit(): View
    {
        $account = Auth::guard('account')->user();
        $prefs = array_merge(self::DEFAULTS, (array) ($account->meta['prefs'] ?? []));

        return view('account.preferences', [
            'account' => $account,
            'prefs' => $prefs,
            'langs' => self::LANGS,
            'timezones' => self::TIMEZONES,
            'dateFormats' => self::DATE_FORMATS,
            'themes' => self::THEMES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lang' => ['nullable', 'in:'.implode(',', array_keys(self::LANGS))],
            'tz' => ['nullable', 'in:'.implode(',', array_keys(self::TIMEZONES))],
            'date_format' => ['nullable', 'in:'.implode(',', self::DATE_FORMATS)],
            'theme' => ['nullable', 'in:'.implode(',', self::THEMES)],
        ]);

        $account = Auth::guard('account')->user();
        $meta = (array) $account->meta;
        $meta['prefs'] = [
            'lang' => $data['lang'] ?? self::DEFAULTS['lang'],
            'tz' => $data['tz'] ?? self::DEFAULTS['tz'],
            'date_format' => $data['date_format'] ?? self::DEFAULTS['date_format'],
            'theme' => $data['theme'] ?? self::DEFAULTS['theme'],
            'comm_transactional' => $request->boolean('comm_transactional'),
            'comm_sms' => $request->boolean('comm_sms'),
            'comm_newsletter' => $request->boolean('comm_newsletter'),
        ];
        $account->meta = $meta;
        $account->save();

        return back()->with('ukc_status', 'Préférences enregistrées.');
    }
}
