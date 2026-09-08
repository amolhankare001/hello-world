<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\Division;
use App\Models\HolisticDomain;
use App\Models\Mentor;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMentorAssignment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = collect([
                RoleCode::SuperAdmin->value => ['name' => 'Super Administrator', 'description' => 'Platform-wide administration'],
                RoleCode::SchoolAdmin->value => ['name' => 'School Administrator', 'description' => 'School configuration and oversight'],
                RoleCode::Mentor->value => ['name' => 'Mentor', 'description' => 'Assigned student support and observation'],
                RoleCode::Student->value => ['name' => 'Student', 'description' => 'Personalized learning access'],
            ])->mapWithKeys(fn (array $attributes, string $code) => [
                $code => Role::query()->create(['code' => $code, ...$attributes]),
            ]);

            $school = School::factory()->create([
                'code' => 'DEMO-SCHOOL',
                'name' => 'Dnyandeep Primary School',
                'name_marathi' => 'ज्ञानदीप प्राथमिक विद्यालय',
                'email' => 'school@example.test',
            ]);

            $academicYear = AcademicYear::factory()->for($school)->create();
            $schoolClass = SchoolClass::factory()->for($school)->create([
                'name' => 'Standard 4',
                'name_marathi' => 'इयत्ता चौथी',
                'grade_level' => 4,
            ]);
            $division = Division::factory()->for($schoolClass)->create([
                'name' => 'A',
                'name_marathi' => 'अ',
            ]);

            $platformAdministrator = User::factory()->create([
                'role_id' => $roles[RoleCode::SuperAdmin->value]->id,
                'name' => 'Platform Administrator',
                'email' => 'admin@example.test',
                'preferred_locale' => 'en',
            ]);

            User::factory()->create([
                'role_id' => $roles[RoleCode::SchoolAdmin->value]->id,
                'school_id' => $school->id,
                'name' => 'School Administrator',
                'email' => 'school-admin@example.test',
            ]);

            $mentorUser = User::factory()->create([
                'role_id' => $roles[RoleCode::Mentor->value]->id,
                'school_id' => $school->id,
                'name' => 'Anjali Patil',
                'email' => 'mentor@example.test',
            ]);
            $mentor = Mentor::factory()->for($mentorUser)->for($school)->create([
                'employee_number' => 'MEN-001',
            ]);

            $students = collect(range(1, 15))->map(function (int $number) use (
                $academicYear,
                $division,
                $mentor,
                $roles,
                $school
            ): Student {
                $user = User::factory()->create([
                    'role_id' => $roles[RoleCode::Student->value]->id,
                    'school_id' => $school->id,
                    'name' => "Demo Student {$number}",
                    'email' => "student{$number}@example.test",
                ]);

                $student = Student::factory()->for($user)->for($school)->create([
                    'student_number' => sprintf('STU-%03d', $number),
                ]);

                StudentEnrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'division_id' => $division->id,
                    'roll_number' => (string) $number,
                    'enrolled_on' => $academicYear->starts_on,
                    'status' => 'active',
                ]);

                StudentMentorAssignment::query()->create([
                    'student_id' => $student->id,
                    'mentor_id' => $mentor->id,
                    'academic_year_id' => $academicYear->id,
                    'assigned_on' => $academicYear->starts_on,
                    'is_primary' => true,
                ]);

                return $student;
            });

            $marathi = Subject::query()->create([
                'code' => 'MARATHI',
                'name' => 'Marathi',
                'name_marathi' => 'मराठी',
                'sort_order' => 1,
            ]);
            $mathematics = Subject::query()->create([
                'code' => 'MATHEMATICS',
                'name' => 'Mathematics',
                'name_marathi' => 'गणित',
                'sort_order' => 2,
            ]);

            $this->createSkills($marathi, $platformAdministrator, [
                ['LETTER_RECOGNITION', 'Letter recognition', 'अक्षर ओळख'],
                ['VOWELS', 'Vowels', 'स्वर'],
                ['CONSONANTS', 'Consonants', 'व्यंजन'],
                ['VOWEL_MARKS', 'Vowel marks', 'मात्रा'],
                ['BARAKHADI', 'Barakhadi', 'बाराखडी'],
                ['LETTER_JOINING', 'Letter joining', 'अक्षर जोडणी'],
                ['WORD_RECOGNITION', 'Word recognition', 'शब्द ओळख'],
                ['WORD_FORMATION', 'Word formation', 'शब्द तयार करणे'],
                ['WORD_READING', 'Word reading', 'शब्द वाचन'],
                ['SENTENCE_READING', 'Sentence reading', 'वाक्य वाचन'],
                ['SENTENCE_FORMATION', 'Sentence formation', 'वाक्य तयार करणे'],
                ['DICTATION', 'Dictation', 'श्रुतलेखन'],
                ['VOCABULARY', 'Vocabulary', 'शब्दसंग्रह'],
                ['COMPREHENSION', 'Comprehension', 'आकलन'],
                ['PARAGRAPH_READING', 'Paragraph reading', 'परिच्छेद वाचन'],
            ]);

            $this->createSkills($mathematics, $platformAdministrator, [
                ['NUMBER_RECOGNITION', 'Number recognition', 'संख्या ओळख'],
                ['NUMBER_READING', 'Number reading', 'संख्या वाचन'],
                ['NUMBER_COMPARISON', 'Number comparison', 'संख्या तुलना'],
                ['NUMBER_ORDERING', 'Number ordering', 'संख्या क्रम'],
                ['PLACE_VALUE', 'Place value', 'स्थानिक किंमत'],
                ['ADDITION', 'Addition', 'बेरीज'],
                ['SUBTRACTION', 'Subtraction', 'वजाबाकी'],
                ['MULTIPLICATION', 'Multiplication', 'गुणाकार'],
                ['DIVISION', 'Division', 'भागाकार'],
                ['FRACTIONS', 'Fractions', 'अपूर्णांक'],
                ['TIME', 'Time', 'वेळ'],
                ['MONEY', 'Money', 'पैसे'],
                ['MEASUREMENT', 'Measurement', 'मोजमाप'],
                ['GEOMETRIC_SHAPES', 'Geometric shapes', 'भूमितीय आकार'],
                ['WORD_PROBLEMS', 'Word problems', 'शब्दसमस्या'],
            ]);

            Badge::query()->create([
                'code' => 'FIRST_STEP',
                'name' => 'First Step',
                'name_marathi' => 'पहिले पाऊल',
                'description' => 'Complete the first learning activity.',
                'description_marathi' => 'पहिली अध्ययन कृती पूर्ण करा.',
                'criteria' => ['activity_count' => 1],
                'xp_bonus' => 10,
            ]);

            foreach ([
                ['LEARNING', 'Learning habits', 'अध्ययन सवयी'],
                ['COMMUNICATION', 'Communication', 'संवाद'],
                ['SOCIAL_EMOTIONAL', 'Social and emotional growth', 'सामाजिक आणि भावनिक विकास'],
            ] as $index => [$code, $name, $nameMarathi]) {
                HolisticDomain::query()->create([
                    'code' => $code,
                    'name' => $name,
                    'name_marathi' => $nameMarathi,
                    'sort_order' => $index + 1,
                ]);
            }

            $students->each(fn (Student $student) => $student->skillProgress()->createMany(
                Skill::query()->get()->map(fn (Skill $skill) => [
                    'skill_id' => $skill->id,
                    'academic_year_id' => $academicYear->id,
                ])->all()
            ));
        });
    }

    /**
     * @param  list<array{string, string, string}>  $skills
     */
    private function createSkills(Subject $subject, User $creator, array $skills): void
    {
        foreach ($skills as $index => [$code, $name, $nameMarathi]) {
            $skill = $subject->skills()->create([
                'code' => $code,
                'name' => $name,
                'name_marathi' => $nameMarathi,
                'sort_order' => $index + 1,
            ]);

            $levels = $skill->levels()->createMany([
                [
                    'level' => 1,
                    'name' => 'Foundation',
                    'name_marathi' => 'पायाभूत',
                    'learning_objective' => "Recognize and understand the basics of {$name}.",
                    'mastery_threshold' => 70,
                ],
                [
                    'level' => 2,
                    'name' => 'Developing',
                    'name_marathi' => 'विकसनशील',
                    'learning_objective' => "Apply {$name} with guided practice.",
                    'mastery_threshold' => 80,
                ],
                [
                    'level' => 3,
                    'name' => 'Mastery',
                    'name_marathi' => 'प्रावीण्य',
                    'learning_objective' => "Use {$name} independently and accurately.",
                    'mastery_threshold' => 90,
                ],
            ]);

            Activity::query()->create([
                'skill_id' => $skill->id,
                'skill_level_id' => $levels->first()->id,
                'created_by' => $creator->id,
                'code' => "{$subject->code}_{$code}_LEARN_1",
                'type' => 'learn',
                'title' => "Learn {$name}",
                'title_marathi' => "{$nameMarathi} शिका",
                'instructions' => 'Read the explanation and examples before starting practice.',
                'instructions_marathi' => 'सराव सुरू करण्यापूर्वी स्पष्टीकरण आणि उदाहरणे वाचा.',
                'content' => [
                    'body' => "A guided introduction to {$name}.",
                    'body_marathi' => "{$nameMarathi} या कौशल्याची मार्गदर्शित ओळख.",
                    'examples' => [],
                ],
                'difficulty' => 1,
                'estimated_minutes' => 10,
                'max_score' => 0,
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
    }
}
