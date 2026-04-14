<?php

namespace App\Policies;

use App\Models\ContentNode;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContentNodePolicy
{
    use ChecksPermissions;

    public function view(User $user, ContentNode $node): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function update(User $user, ContentNode $node): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function delete(User $user, ContentNode $node): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }
}
