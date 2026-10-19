<?php

namespace App\Actions;

use App\Mail\AuthorInviteMail;
use App\Mail\AuthorLinkedMail;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Sms\SmsManager;
use App\Support\AuthorMatcher;
use App\Support\EventNameFormat;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Rapproche les auteurs d'une communication (correspondant + co-auteurs) des
 * comptes de la plateforme, puis crée les liaisons et notifie :
 * - **utilisateur trouvé** → lien + participant + notif in-app / e-mail / SMS ;
 *   la communication apparaît dans « Mes communications » de la personne ;
 * - **personne non inscrite** → e-mail (+ SMS) d'invitation à créer son compte,
 *   la liaison est mémorisée (`invited`) pour être activée à l'inscription.
 *
 * Le déposant lui-même est enregistré comme lien `self` (jamais notifié).
 */
class LinkSubmissionAuthors
{
    public function __construct(
        protected AuthorMatcher $matcher,
        protected UpsertParticipantForAccount $upsert,
        protected SmsManager $sms,
    ) {}

    public function handle(Submission $submission, User $submitter): void
    {
        // Idempotent : on ne (re)construit les liens que s'ils n'existent pas.
        if ($submission->authorLinks()->exists()) {
            return;
        }

        $event = $submission->event;
        if (! $event) {
            return;
        }

        foreach (($submission->authors ?? []) as $index => $author) {
            $role = ($author['corresponding'] ?? false) ? 'corresponding' : 'coauthor';

            $link = new SubmissionAuthor([
                'submission_id' => $submission->id,
                'event_id' => $event->id,
                'role' => $role,
                'author_index' => (int) $index,
                'name' => (string) ($author['name'] ?? ''),
                'email' => AuthorMatcher::normEmail($author['email'] ?? null) ?: null,
                'phone' => ($author['phone'] ?? null) ?: null,
                'affiliation' => ($author['affiliation'] ?? null) ?: null,
                'token' => Str::random(48),
                'status' => 'unmatched',
            ]);

            // Le déposant (auteur correspondant « self ») : lien direct, sans notif.
            if ($role === 'corresponding' && ($author['is_submitter'] ?? false)) {
                $link->user_id = $submitter->id;
                $link->match_type = 'self';
                $link->status = 'linked';
                $link->save();

                continue;
            }

            $match = $this->matcher->match($author);
            $user = $match['user'];

            if ($user && $user->id !== $submitter->id) {
                $this->linkUser($submission, $event, $link, $user, $role, (string) $match['type']);
            } elseif ($user && $user->id === $submitter->id) {
                // Un « co-auteur » qui est en fait le déposant : déjà propriétaire.
                $link->user_id = $submitter->id;
                $link->match_type = 'self';
                $link->status = 'linked';
                $link->save();
            } elseif ($link->email) {
                $this->invite($submission, $event, $link, $role);
            } else {
                $link->save(); // status unmatched, aucun contact fiable
            }
        }
    }

    /** Lie un compte existant : participant + notification + e-mail (+ SMS). */
    protected function linkUser(Submission $submission, $event, SubmissionAuthor $link, User $user, string $role, string $type): void
    {
        $this->upsert->handle($event, $user, $role === 'corresponding' ? 'author' : 'coauthor');

        $link->user_id = $user->id;
        $link->match_type = $type;
        $link->status = 'linked';
        $link->notified_at = now();
        $link->save();

        $roleLabel = self::roleLabel($role);
        $url = route('account.submissions.show', $submission);
        $declineUrl = route('author.decline', ['token' => $link->token]);

        Notifier::account(
            $user,
            'authorship',
            __('Vous êtes associé(e) à une communication'),
            '« '.$submission->title.' » - '.($event->title ?? ''),
            $url,
            'inbox',
        );

        if (Notifier::emailAllowed($user, 'authorship')) {
            Mail::to($user->email)->send(new AuthorLinkedMail($submission, EventNameFormat::user($event, $user), $roleLabel, $url, $declineUrl));
        }

        $this->maybeSms($link->phone ?: $user->phone, __('Vous êtes associé(e) à la communication « :t » pour :e. Détails dans votre espace.', ['t' => Str::limit($submission->title, 60), 'e' => $event->title ?? '']));
    }

    /** Personne non inscrite : e-mail (+ SMS) d'invitation à créer son compte. */
    protected function invite(Submission $submission, $event, SubmissionAuthor $link, string $role): void
    {
        $link->status = 'invited';
        $link->invited_at = now();
        $link->save();

        $registerUrl = route('account.register', ['email' => $link->email]);
        $declineUrl = route('author.decline', ['token' => $link->token]);

        Mail::to($link->email)->send(new AuthorInviteMail($submission, $link->name, self::roleLabel($role), $registerUrl, $declineUrl));

        $this->maybeSms($link->phone, __('Vous êtes cité(e) comme auteur d’une communication pour :e. Créez votre compte pour la retrouver : :u', ['e' => $event->title ?? '', 'u' => $registerUrl]));
    }

    /** Envoi SMS best-effort : seulement si un numéro est fourni ET une passerelle configurée. */
    protected function maybeSms(?string $phone, string $message): void
    {
        $phone = trim((string) $phone);
        if ($phone === '' || config('sms.default', 'log') === 'log') {
            return;
        }

        try {
            $this->sms->send($phone, $message);
        } catch (\Throwable) {
            // Best-effort : un échec SMS ne doit pas casser la soumission.
        }
    }

    public static function roleLabel(string $role): string
    {
        return $role === 'corresponding' ? __('Auteur correspondant') : __('Co-auteur');
    }
}
