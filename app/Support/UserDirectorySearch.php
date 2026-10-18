<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/** Recherche de comptes par nom, prénom ou e-mail pour les sélecteurs du back-office. */
class UserDirectorySearch
{
    /** @return Collection<int, User> */
    public static function find(string $query): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return collect();
        }

        $terms = preg_split('/\s+/', $query) ?: [$query];

        return User::query()
            ->whereNotNull('email')->where('email', '!=', '')
            ->where(function ($where) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $where->where(fn ($part) => $part->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like));
                }
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->limit(10)
            ->get();
    }
}
