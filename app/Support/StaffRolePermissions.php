<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

/** Permissions des fonctions d'équipe, chargées une fois par requête. */
class StaffRolePermissions
{
    /** @var array<string, list<string>>|null */
    private ?array $stored = null;

    /** @return list<string> */
    public function forRole(string $role): array
    {
        if (! isset(StaffRoles::ROLES[$role])) {
            return [];
        }

        $this->stored ??= Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(StaffRoles::ROLES))
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $item) => [$item->name => $item->permissions
                ->pluck('name')->intersect(array_keys(StaffRoles::PERMISSIONS))
                ->values()->all()])
            ->all();

        return $this->stored[$role] ?? StaffRoles::ROLES[$role]['permissions'];
    }

    public function forget(): void
    {
        $this->stored = null;
    }
}
