<?php

namespace App\Policies;

use App\Models\Law;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LawPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::LAWS_MANAGE);
    }

    public function view(User $user, Law $law): bool
    {
        return $this->allows($user, Permission::LAWS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::LAWS_MANAGE);
    }

    public function update(User $user, Law $law): bool
    {
        return $this->allows($user, Permission::LAWS_MANAGE);
    }

    public function delete(User $user, Law $law): bool
    {
        return $this->allows($user, Permission::LAWS_MANAGE);
    }
}
