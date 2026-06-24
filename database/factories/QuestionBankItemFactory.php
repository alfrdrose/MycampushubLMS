<?php

namespace Database\Factories;

use App\Models\QuestionBankItem;
use App\Models\SectionModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionBankItem>
 */
class QuestionBankItemFactory extends Factory
{
    protected $model = QuestionBankItem::class;

    public function definition(): array
    {
        return [
            'created_by_teacher_id' => TeacherModel::factory(),
            'subject_model_id' => SubjectModel::factory(),
            'section_model_id' => null,
            'type' => 'multiple_choice',
            'text' => $this->faker->sentence() . '?',
            'option' => ['Option 1', 'Option 2', 'Option 3'],
            'setting' => null,
            'answer_key' => ['Option 1'],
            'required' => false,
            'default_points' => 1,
            'is_archived' => false,
            'archived_at' => null,
        ];
    }

    public function sectionScoped(?SectionModel $section = null): static
    {
        return $this->state(function () use ($section) {
            $section ??= SectionModel::factory();

            return [
                'section_model_id' => $section->id,
            ];
        });
    }
}
