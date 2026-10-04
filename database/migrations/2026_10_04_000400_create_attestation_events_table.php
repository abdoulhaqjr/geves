<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * **Journal de vie d'une attestation délivrée** : ouverte, téléchargée,
 * partagée, emportée vers le formulaire « Ajouter au profil » de LinkedIn.
 *
 * ⚠️ **Délivrer n'est pas être reçu.** `delivered_at` dit que le secrétariat a
 * tendu le papier ; rien ne disait si une attestation EN LIGNE était jamais
 * arrivée à son destinataire. Une attestation émise, notifiée et jamais ouverte
 * est indiscernable d'une attestation lue et partagée - et c'est pourtant la
 * seule chose que le gestionnaire veut savoir avant de relancer.
 *
 * ⚠️ **Aucune adresse IP n'est conservée.** `fingerprint` est un HMAC tronqué
 * (clé de l'application) d'IP + agent : il ne sert qu'à ne pas compter dix
 * ouvertures quand quelqu'un rafraîchit la page, et ne se renverse pas en
 * adresse. Un journal de consultation d'un document nominatif n'a pas besoin de
 * savoir d'où l'on vient, seulement qu'on est déjà venu.
 *
 * ⚠️ `actor` sépare le TITULAIRE (connecté à son espace) du public et du staff :
 * sans cette distinction, le clic du gestionnaire sur « Document » depuis sa
 * propre liste marquerait toutes les attestations comme ouvertes, et
 * l'ouverture d'un recruteur passerait pour un accusé de réception.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attestation_id')->constrained('attestations')->cascadeOnDelete();
            // view, download, share, copy, linkedin (cf. AttestationEvent::KINDS).
            $table->string('kind', 20);
            // holder, public, staff.
            $table->string('actor', 10)->default('public');
            $table->string('fingerprint', 16)->nullable();
            // D'où vient le visiteur, au domaine près (jamais l'URL complète).
            $table->string('referer_host', 100)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            // Le dédoublonnage interroge exactement ces trois colonnes.
            $table->index(['attestation_id', 'kind', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestation_events');
    }
};
