<?php

namespace App\Models\Scopes;

use App\Models\SkillPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Keeps the Plant Manager - the company's highest leader, recorded only so
 * people know who it is - out of every Employee query: lists, totals,
 * dashboards, the org chart, pickers. Added as a global scope so no screen
 * has to remember to exclude them; the dedicated Plant Manager menu opts out
 * with Employee::withPlantManager().
 *
 * "Plant Manager" is whoever holds the skill position of that name (see
 * SkillPosition::PLANT_MANAGER).
 */
class ExcludePlantManagerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $column = $model->qualifyColumn('skill_position_id');

        $builder->where(fn (Builder $q) => $q
            ->whereNull($column)
            ->orWhereNotIn($column, SkillPosition::plantManagerIds()));
    }
}
