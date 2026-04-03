<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'year_start',
        'year_end',
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return static::query()
            ->active()
            ->orderByDesc('year_start')
            ->first();
    }
}
