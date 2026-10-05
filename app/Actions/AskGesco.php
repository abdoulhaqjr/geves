<?php

namespace App\Actions;

use App\AI\AiManager;
use App\AI\AiRouter;
use App\Models\Event;
use App\Support\AiAgents;
use App\Support\EventKnowledge;
use Illuminate\Support\Str;

/**
 * Répond à une question de participant via l'assistante Gesco. Utilise la
 * configuration de l'agent « gesco » (persona + instructions POSITIVES /
 * NÉGATIVES, cf. {@see AiAgents}) et le contexte de l'événement active. Gesco
 * informe et oriente : elle ne réalise aucune action à la place de l'utilisateur.
 *
 * @phpstan-type ChatTurn array{role: string, text: string}
 */
class AskGesco
{
    public function __construct(protected AiManager $manager, protected AiRouter $router) {}

    /**
     * @param  list<ChatTurn>  $history  tours précédents (rôle bot|user)
     * @param  string|null  $personal  résumé (données réelles) de l'utilisateur connecté, ou null si visiteur anonyme
     * @param  array{name: ?string, email: ?string}|null  $contact  identité de l'utilisateur connecté (pour une éventuelle réclamation)
     * @return array{answer: string, claim: array<string, mixed>|null}
     */
    public function handle(array $history, string $question, ?Event $event = null, ?string $personal = null, ?array $contact = null): array
    {
        if (! $this->manager->resolve(false)) {
            throw new \RuntimeException('L’assistance Gesco est momentanément indisponible.');
        }

        // Palier de modèle choisi selon la complexité (économie sur les questions simples).
        $tier = $this->router->tier('chat_public', $question, historyTurns: count($history));

        // Bascule automatique entre fournisseurs configurés en cas d'échec.
        $raw = $this->manager->extract($this->instruction($history, $question, $event, $personal, $contact), [], $tier);

        $answer = trim((string) ($raw['answer'] ?? ''));

        return [
            'answer' => $answer !== ''
                ? Str::limit($answer, 4000, '')
                : 'Je ne suis pas sûre de pouvoir répondre à cela. Contactez la DRI à dri@univkara.tg pour toute question précise.',
            'claim' => $this->sanitizeClaim($raw['claim'] ?? null),
        ];
    }

    /**
     * Normalise l'éventuelle réclamation proposée par l'IA.
     *
     * @return array{type: string, category: string, subject: string, summary: string, contactName: string, contactEmail: string}|null
     */
    protected function sanitizeClaim(mixed $claim): ?array
    {
        if (! is_array($claim) || empty($claim['record'])) {
            return null;
        }

        $subject = Str::limit(trim((string) ($claim['subject'] ?? '')), 160, '');
        $summary = Str::limit(trim((string) ($claim['summary'] ?? '')), 4000, '');
        if ($subject === '' && $summary === '') {
            return null;
        }

        return [
            'type' => in_array($claim['type'] ?? '', ['reclamation', 'incident'], true) ? (string) $claim['type'] : 'reclamation',
            'category' => Str::limit(trim((string) ($claim['category'] ?? '')), 40, ''),
            'subject' => $subject !== '' ? $subject : Str::limit($summary, 80, ''),
            'summary' => $summary !== '' ? $summary : $subject,
            'contactName' => Str::limit(trim((string) ($claim['contactName'] ?? '')), 120, ''),
            'contactEmail' => Str::limit(trim((string) ($claim['contactEmail'] ?? '')), 190, ''),
        ];
    }

    /**
     * @param  list<ChatTurn>  $history
     * @param  array{name: ?string, email: ?string}|null  $contact
     */
    protected function instruction(array $history, string $question, ?Event $event, ?string $personal = null, ?array $contact = null): string
    {
        $agent = AiAgents::find('gesco') ?? AiAgents::defaults()[0];

        // Base de connaissance factuelle (tarifs, axes, échéances, règles, FAQ…) :
        // c'est la source prioritaire pour répondre avec précision.
        $knowledge = EventKnowledge::brief($event);
        $context = $knowledge !== '' ? $knowledge : 'Aucune événement active pour le moment.';

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

        // Identité de contact (pour l'enregistrement d'une éventuelle réclamation).
        $contactBlock = filled($contact['email'] ?? null)
            ? 'CONTACT CONNU (utilisateur connecté) : '.trim((string) ($contact['name'] ?? '')).' <'.$contact['email'].'>. '
                ."Utilise ce contact pour une réclamation sans le redemander.\n\n"
            : '';

        // Horloge : la date/heure courantes permettent de répondre aux questions
        // temporelles (aujourd'hui, échéances à venir/passées, délais restants).
        $now = now()->locale('fr')->translatedFormat('l j F Y, H:i');

        return <<<TXT
        {$agent['persona']}

        À TOUJOURS FAIRE :
        {$agent['positive']}

        À NE JAMAIS FAIRE :
        {$agent['negative']}

        DATE ET HEURE ACTUELLES : {$now}. Appuie-toi dessus pour toute question temporelle
        (« quel jour sommes-nous ? », échéances à venir ou passées, délais restants).

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

        {$personalBlock}{$contactBlock}RÉCLAMATIONS & INCIDENTS :
        - Si l'utilisateur exprime une réclamation, un problème, un incident ou une insatisfaction,
          commence TOUJOURS par essayer de l'aider ou de résoudre à partir de la base de connaissance
          (procédure, page concernée, contact). Ne l'enregistre pas si tu peux régler le souci.
        - Si tu réponds/résous pleinement, mets "claim" à null.
        - Si tu NE PEUX PAS résoudre et que l'utilisateur souhaite signaler/enregistrer son souci,
          prépare "claim" avec record=true : "type" ("reclamation" par défaut, "incident" pour un
          dysfonctionnement technique), "category" (paiement, soumission, inscription, badge,
          attestation, programme, technique, autre), "subject" (titre court) et "summary" (résumé
          fidèle et complet du problème, avec les éléments utiles au traitement).
        - CONTACT pour le suivi : n'enregistre (record=true) que si tu disposes d'un e-mail.
          Pour un utilisateur CONNECTÉ, utilise son identité (déjà connue, ne la redemande pas).
          Pour un utilisateur ANONYME, demande d'abord son NOM et son E-MAIL ; renseigne
          "contactName"/"contactEmail" uniquement quand il te les a fournis. Sans e-mail, garde
          record=false et invite-le poliment à donner un e-mail ou à se connecter.

        Historique récent :
        {$convo}

        Nouvelle question de l'utilisateur : {$question}

        Réponds de façon utile, claire et concise. Structure ta réponse pour qu'elle soit
        facile à lire : phrases courtes, un saut de ligne entre les idées, une liste à puces
        (« - ») quand tu énumères des étapes ou des éléments, et **gras** pour les points clés.
        N'utilise ni titres « # » ni tableaux. Reste bref (pas de remplissage).
        Quand tu enregistres une réclamation, confirme-le dans "answer" (l'utilisateur recevra une
        référence de suivi).

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "answer": string, "claim": { "record": boolean, "type": string, "category": string,
          "subject": string, "summary": string, "contactName": string, "contactEmail": string } | null }.
        Le texte de "answer" peut contenir des sauts de ligne (\n).
        TXT;
    }
}
