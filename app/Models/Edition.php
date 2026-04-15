<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    protected $fillable = [
        'name',
        'code',
        'year_start',
        'year_end',
        'status',
        'is_active',
    ];

    protected $casts = [
        'id' => 'integer',
        'year_start' => 'integer',
        'year_end' => 'integer',
        'is_active' => 'boolean',
    ];

    public function laws(): HasMany
    {
        return $this->hasMany(Law::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            return $this->whereKey($value)->first();
        }

        return $this->newQuery()->where('code', (string) $value)->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public static function current(): ?self
    {
        return static::query()
            ->active()
            ->published()
            ->orderByDesc('year_start')
            ->first();
    }
}
