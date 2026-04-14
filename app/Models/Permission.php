<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'code', 'description'])]
class Permission extends Model
{
    public const ADMIN_ACCESS = 'admin.access';
    public const EDITIONS_MANAGE = 'editions.manage';
    public const LAWS_MANAGE = 'laws.manage';
    public const NODES_MANAGE = 'nodes.manage';
    public const DOCUMENTS_MANAGE = 'documents.manage';
    public const QAS_MANAGE = 'qas.manage';
    public const CHANGELOG_MANAGE = 'changelog.manage';

    public $timestamps = false;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return array<int, array{name: string, code: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Access admin',
                'code' => self::ADMIN_ACCESS,
                'description' => 'Access the internal admin area.',
            ],
            [
                'name' => 'Manage editions',
                'code' => self::EDITIONS_MANAGE,
                'description' => 'Create, update, activate, and delete editions.',
            ],
            [
                'name' => 'Manage laws',
                'code' => self::LAWS_MANAGE,
                'description' => 'Create and update laws.',
            ],
            [
                'name' => 'Manage nodes',
                'code' => self::NODES_MANAGE,
                'description' => 'Create, update, and delete law content nodes.',
            ],
            [
                'name' => 'Manage documents',
                'code' => self::DOCUMENTS_MANAGE,
                'description' => 'Create and update supporting documents.',
            ],
            [
                'name' => 'Manage law Q&A',
                'code' => self::QAS_MANAGE,
                'description' => 'Create, update, and delete law Q&A items.',
            ],
            [
                'name' => 'Manage changelog',
                'code' => self::CHANGELOG_MANAGE,
                'description' => 'Create and update law change entries.',
            ],
        ];
    }
}
