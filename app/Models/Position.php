<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['department_id', 'maintenance_area_id', 'title', 'code', 'grade_level', 'description', 'is_active'])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function maintenanceArea(): BelongsTo
    {
        return $this->belongsTo(MaintenanceArea::class);
    }

    public function skillRequirements(): HasMany
    {
        return $this->hasMany(PositionSkillRequirement::class);
    }

    /** Default skill checklist for everyone holding this position. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'position_skills')->withTimestamps();
    }

    public function jobDescriptions(): HasMany
    {
        return $this->hasMany(JobDescription::class);
    }

    public function currentJobDescription(): ?JobDescription
    {
        return $this->jobDescriptions()->where('status', 'active')->latest('version')->first();
    }
}
