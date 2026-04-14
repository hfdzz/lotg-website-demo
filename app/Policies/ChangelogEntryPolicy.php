<?php

namespace App\Policies;

use App\Models\ChangelogEntry;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ChangelogEntryPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::CHANGELOG_MANAGE);
    }

    public function view(User $user, ChangelogEntry $entry): bool
    {
        return $this->allows($user, Permission::CHANGELOG_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::CHANGELOG_MANAGE);
    }

    public function update(User $user, ChangelogEntry $entry): bool
    {
        return $this->allows($user, Permission::CHANGELOG_MANAGE);
    }

    public function delete(User $user, ChangelogEntry $entry): bool
    {
        return $this->allows($user, Permission::CHANGELOG_MANAGE);
    }
}
