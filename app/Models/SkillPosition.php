<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[Fillable(['name', 'code', 'description', 'is_active'])]
class SkillPosition extends Model
{
    /** @use HasFactory<\Database\Factories\SkillPositionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Employees holding this skill position are the company's highest
     * leadership: kept out of every employee list and count, and managed
     * only from the dedicated Plant Manager menu (see ExcludePlantManagerScope).
     */
    public const PLANT_MANAGER = 'Plant Manager';

    /**
     * Subquery of the Plant Manager skill position id(s), including a
     * soft-deleted one so archiving the position can't suddenly unhide them.
     */
    public static function plantManagerIds(): QueryBuilder
    {
        return static::withTrashed()->where('name', self::PLANT_MANAGER)->select('id')->toBase();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
