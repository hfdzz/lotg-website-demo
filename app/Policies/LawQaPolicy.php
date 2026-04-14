<?php

namespace App\Policies;

use App\Models\LawQa;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LawQaPolicy
{
    use ChecksPermissions;

    public function view(User $user, LawQa $qa): bool
    {
        return $this->allows($user, Permission::QAS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::QAS_MANAGE);
    }

    public function update(User $user, LawQa $qa): bool
    {
        return $this->allows($user, Permission::QAS_MANAGE);
    }

    public function delete(User $user, LawQa $qa): bool
    {
        return $this->allows($user, Permission::QAS_MANAGE);
    }
}
