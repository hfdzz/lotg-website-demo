<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureVisibility extends Model
{
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_EDITION = 'edition';

    protected $fillable = [
        'feature_key',
        'scope_type',
        'edition_id',
        'is_enabled',
    ];

    protected $casts = [
        'edition_id' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
