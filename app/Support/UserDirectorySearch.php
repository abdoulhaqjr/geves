<?php

namespace App\Support;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/** Recherche de comptes par nom, prénom ou e-mail pour les sélecteurs du back-office. */
class UserDirectorySearch
{
    /** @return Collection<int, User> */
    public static function find(string $query, ?Event $event = null): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return collect();
        }

        $terms = preg_split('/\s+/', $query) ?: [$query];

        $users = User::query()
            ->whereNotNull('email')->where('email', '!=', '')
            ->where(function ($where) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $where->where(fn ($part) => $part->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like));
                }
            });

        if ($event !== null) {
            // L'accès global permet d'administrer l'événement, mais ne fait
            // pas de ce compte un membre de sa liste de personnes.
            $users->where(function ($where) use ($event) {
                $where->where(function ($ordinary) {
                    $ordinary->whereNotIn('access_level', [User::ACCESS_SUPER_ADMIN, User::ACCESS_COLLOQUE_ADMIN])
                        ->whereDoesntHave('roles', fn ($roles) => $roles->where('name', 'administrator')
                            ->orWhereHas('permissions', fn ($permissions) => $permissions->where('name', 'manage_colloques')))
                        ->whereDoesntHave('permissions', fn ($permissions) => $permissions->where('name', 'manage_colloques'));
                })
                    ->orWhereHas('assignedEditions', fn ($editions) => $editions->where('events.id', $event->id))
                    ->orWhereExists(fn ($members) => $members->selectRaw('1')->from('committee_members')
                        ->where('event_id', $event->id)
                        ->whereRaw('LOWER(committee_members.email) = LOWER(users.email)'))
                    ->orWhereExists(fn ($participants) => $participants->selectRaw('1')->from('participants')
                        ->where('event_id', $event->id)
                        ->where(fn ($person) => $person->whereColumn('participants.user_id', 'users.id')
                            ->orWhereRaw('LOWER(participants.email) = LOWER(users.email)')))
                    ->orWhereExists(fn ($sessions) => $sessions->selectRaw('1')->from('event_sessions')
                        ->where('event_id', $event->id)
                        ->where(fn ($person) => $person
                            ->whereRaw('LOWER(event_sessions.moderator_email) = LOWER(users.email)')
                            ->orWhereRaw('LOWER(event_sessions.rapporteur_email) = LOWER(users.email)')));
            });
        }

        return $users->orderBy('first_name')->orderBy('last_name')
            ->limit(10)
            ->get();
    }
}
