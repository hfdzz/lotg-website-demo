<?php

namespace App\Policies;

use App\Models\Edition;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class EditionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }

    public function view(User $user, Edition $edition): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }

    public function update(User $user, Edition $edition): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }

    public function delete(User $user, Edition $edition): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }

    public function activate(User $user, Edition $edition): bool
    {
        return $this->allows($user, Permission::EDITIONS_MANAGE);
    }
}
