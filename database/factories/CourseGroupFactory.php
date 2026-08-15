<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<CourseGroup>
 */
class CourseGroupFactory extends Factory
{
    protected $model = CourseGroup::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::query()->inRandomOrder()->value('id') ?? Course::factory(),
            'academic_term_id' => AcademicTerm::query()->inRandomOrder()->value('id') ?? AcademicTerm::factory(),
            'section_number' => 1,
            'meta_id' => DB::table('metas')->value('id'),
            'modalidad_id' => DB::table('modalidades')->value('id'),
            'cupo' => 30,
        ];
    }
}
