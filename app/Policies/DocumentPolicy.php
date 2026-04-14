<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Permission;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, Permission::DOCUMENTS_MANAGE);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->allows($user, Permission::DOCUMENTS_MANAGE);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, Permission::DOCUMENTS_MANAGE);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->allows($user, Permission::DOCUMENTS_MANAGE);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->allows($user, Permission::DOCUMENTS_MANAGE);
    }
}
