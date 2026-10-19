<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns of the master skill list kept in Excel (Skill Code, Category,
     * Type, Skill Indicator, Sub Evaluation, SOP Number, Plant, Business
     * Unit, Department, Area FL, Dept FL, Class). Skill Code -> `code`,
     * Skill Description -> `name`/`description`, Category -> skill_category_id.
     */
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->string('discipline')->nullable()->after('skill_type'); // MECHANIC / ELECTRIC / OTHER
            $table->string('skill_indicator')->nullable()->after('discipline');
            $table->string('sub_evaluation')->nullable()->after('skill_indicator');
            $table->text('sop_number')->nullable()->after('sub_evaluation');
            $table->string('plant')->nullable()->after('sop_number');
            $table->string('business_unit')->nullable()->after('plant');
            $table->string('department')->nullable()->after('business_unit');
            $table->string('area_fl')->nullable()->after('department');
            $table->string('dept_fl')->nullable()->after('area_fl');
            $table->string('skill_class')->nullable()->after('dept_fl');
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn([
                'discipline', 'skill_indicator', 'sub_evaluation', 'sop_number', 'plant',
                'business_unit', 'department', 'area_fl', 'dept_fl', 'skill_class',
            ]);
        });
    }
};
