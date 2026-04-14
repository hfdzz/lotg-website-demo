<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksPermissions
{
    protected function allows(User $user, string $permissionCode): bool
    {
        return $user->hasPermissionTo($permissionCode);
    }
}
