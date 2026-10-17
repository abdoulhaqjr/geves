<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ **CashPay - c'est-à-dire SEMOA - a été retirée du projet**, et ses
 * identifiants n'ont pas à rester en base. Ce sont des SECRETS marchands (mot
 * de passe, client secret, clé de vérification du callback) : les laisser
 * dormir pour une passerelle que plus rien n'appelle, c'est les exposer sans
 * aucune contrepartie.
 *
 * Deux dépôts, parce qu'il y en a deux : les réglages de la PLATEFORME
 * (`settings`, clés `cashpay.*`) et ceux PROPRES à chaque événement
 * (`events.settings`, sous `payment.cashpay`). N'en nettoyer qu'un laissait la
 * moitié des secrets en place.
 *
 * ⚠️ **Les PAIEMENTS ne sont pas touchés.** Un règlement déjà encaissé garde sa
 * clé `cashpay` et son libellé ({@see Payment::ONLINE_METHODS}) :
 * effacer l'historique d'un encaissement pour faire disparaître une passerelle,
 * ce serait réécrire des comptes.
 *
 * ⚠️ `down()` est vide **à dessein** : on ne restaure pas des secrets qu'on
 * vient de supprimer, et la passerelle qui les lisait n'existe plus.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'like', 'cashpay.%')->delete();

        // La passerelle par défaut ne peut plus désigner CashPay : sans ce
        // retour au manuel, le réglage pointerait sur une passerelle absente et
        // le registre retomberait silencieusement sur le manuel à chaque appel -
        // le même comportement, mais affiché nulle part.
        DB::table('settings')
            ->where('key', 'payments.default')
            ->where('value', json_encode('cashpay'))
            ->update(['value' => json_encode('manual')]);

        foreach (DB::table('events')->select('id', 'settings')->get() as $row) {
            $settings = json_decode((string) $row->settings, true);
            if (! is_array($settings) || ! is_array($settings['payment'] ?? null)) {
                continue;
            }

            $avant = $settings['payment'];
            unset($settings['payment']['cashpay']);

            if (($settings['payment']['gateway'] ?? null) === 'cashpay') {
                unset($settings['payment']['gateway']);
            }

            if ($settings['payment'] === $avant) {
                continue;
            }

            // Un bloc paiement devenu vide s'en va plutôt que de rester en
            // tableau vide : c'est ce que fait déjà l'enregistrement du
            // formulaire, et `EventPaymentConfig` distingue les deux.
            if ($settings['payment'] === []) {
                unset($settings['payment']);
            }

            DB::table('events')->where('id', $row->id)->update(['settings' => json_encode($settings)]);
        }

        // Les réglages sont lus depuis un cache permanent.
        Cache::forget('app.settings');
    }

    public function down(): void {}
};
