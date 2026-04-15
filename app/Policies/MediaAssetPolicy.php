<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class MediaAssetPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function view(User $user, MediaAsset $mediaAsset): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function update(User $user, MediaAsset $mediaAsset): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }

    public function delete(User $user, MediaAsset $mediaAsset): bool
    {
        return $this->allows($user, Permission::NODES_MANAGE);
    }
}
