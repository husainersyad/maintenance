<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'skill_category_id', 'skill_type', 'description', 'measurement_method', 'is_active',
    'discipline', 'skill_indicator', 'sub_evaluation', 'sop_number', 'plant', 'business_unit', 'department', 'area_fl', 'dept_fl', 'skill_class',
])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function skillCategory(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class);
    }

    public function positionRequirements(): HasMany
    {
        return $this->hasMany(PositionSkillRequirement::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'position_skills')->withTimestamps();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(EmployeeSkillAssessment::class);
    }
}
