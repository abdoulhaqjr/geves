<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Support\AiAgents;
use App\Support\ColloqueKnowledge;
use Illuminate\Support\Str;

/**
 * Répond à une question de participant via l'assistante Gesco. Utilise la
 * configuration de l'agent « gesco » (persona + instructions POSITIVES /
 * NÉGATIVES, cf. {@see AiAgents}) et le contexte de l'édition active. Gesco
 * informe et oriente : elle ne réalise aucune action à la place de l'utilisateur.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskGesco
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  list<ChatTurn>  $history  tours précédents (rôle bot|user)
     * @param  string|null  $personal  résumé (données réelles) de l'utilisateur connecté, ou null si visiteur anonyme
     */
    public function handle(array $history, string $question, ?Edition $edition = null, ?string $personal = null): string
    {
        $provider = $this->manager->resolve(false);
        if (! $provider) {
            throw new \RuntimeException('L’assistance Gesco est momentanément indisponible.');
        }

        $raw = $provider->extract($this->instruction($history, $question, $edition, $personal), []);

        $answer = trim((string) ($raw['answer'] ?? ''));

        return $answer !== ''
            ? Str::limit($answer, 4000, '')
            : 'Je ne suis pas sûre de pouvoir répondre à cela. Contactez la DRI à dri@univkara.tg pour toute question précise.';
    }

    /**
     * @param  list<ChatTurn>  $history
     */
    protected function instruction(array $history, string $question, ?Edition $edition, ?string $personal = null): string
    {
        $agent = AiAgents::find('gesco') ?? AiAgents::defaults()[0];

        // Base de connaissance factuelle (tarifs, axes, échéances, règles, FAQ…) :
        // c'est la source prioritaire pour répondre avec précision.
        $knowledge = ColloqueKnowledge::brief($edition);
        $context = $knowledge !== '' ? $knowledge : 'Aucune édition active pour le moment.';

        // Derniers tours de conversation (bornés).
        $convo = collect($history)
            ->slice(-8)
            ->map(fn (array $t) => ($t['role'] === 'user' ? 'Utilisateur' : 'Gesco').' : '.Str::limit((string) $t['text'], 500, ''))
            ->implode("\n");

        // Bloc « données personnelles » : présent uniquement si l'utilisateur est connecté.
        $personalBlock = $personal !== null && trim($personal) !== ''
            ? "DONNÉES PERSONNELLES DE L'UTILISATEUR CONNECTÉ (réelles, à jour) :\n{$personal}\n\n"
                ."Tu PEUX répondre aux questions personnelles de l'utilisateur (ses soumissions, son paiement, "
                ."son badge, ses attestations, ses échéances) en t'appuyant UNIQUEMENT sur ces données ci-dessus. "
                ."Si l'information demandée n'y figure pas, dis-le et oriente vers la page concernée ou la DRI. "
                ."Ne divulgue JAMAIS les données d'un autre participant.\n\n"
            : "L'utilisateur n'est pas connecté : tu ne disposes d'aucune donnée personnelle. Pour toute question "
                ."personnelle (statut d'une soumission, paiement, badge…), invite-le à se connecter à son espace participant.\n\n";

        return <<<TXT
        {$agent['persona']}

        À TOUJOURS FAIRE :
        {$agent['positive']}

        À NE JAMAIS FAIRE :
        {$agent['negative']}

        ============================================================
        BASE DE CONNAISSANCE OFFICIELLE (informations réelles et à jour du colloque)
        ============================================================
        {$context}
        ============================================================

        RÈGLE DE PRÉCISION (impérative) :
        - Réponds en priorité à partir de la BASE DE CONNAISSANCE ci-dessus. Cite les
          chiffres, dates, montants et noms EXACTEMENT comme ils y figurent (tarifs, mots
          maximum du résumé, dates limites, axes, contacts…). Ne les arrondis pas, ne les
          reformule pas approximativement.
        - Si l'utilisateur pose une question dont la réponse ne figure PAS dans la base,
          dis-le clairement (« Cette information n'est pas encore précisée / je n'ai pas
          cette information ») et oriente vers la page concernée ou le contact de la DRI.
          N'invente JAMAIS un tarif, une date, un chiffre ou une procédure.
        - Reste strictement dans le périmètre de ce colloque. Ne réponds pas à des
          questions hors sujet (culture générale, autres établissements, etc.) : recentre
          poliment.
        - Réponds directement à la question posée, sans digression ni informations non
          demandées.

        {$personalBlock}Historique récent :
        {$convo}

        Nouvelle question de l'utilisateur : {$question}

        Réponds de façon utile, claire et concise. Structure ta réponse pour qu'elle soit
        facile à lire : phrases courtes, un saut de ligne entre les idées, une liste à puces
        (« - ») quand tu énumères des étapes ou des éléments, et **gras** pour les points clés.
        N'utilise ni titres « # » ni tableaux. Reste bref (pas de remplissage).
        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) : { "answer": string }.
        Le texte peut contenir des sauts de ligne (\n).
        TXT;
    }
}
