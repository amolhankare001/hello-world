<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\Division;
use App\Models\Game;
use App\Models\HolisticDomain;
use App\Models\Mentor;
use App\Models\Question;
use App\Models\RecommendationRule;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Simulation;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentMentorAssignment;
use App\Models\Subject;
use App\Models\Test;
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
            $this->createDemoGames($platformAdministrator);
            $this->createDemoSimulations($platformAdministrator);
            $this->createDemoPracticeContent($platformAdministrator);
            $this->createDemoAssessments($school, $academicYear, $schoolClass, $platformAdministrator);
            $this->createRecommendationRules();

            Badge::query()->create([
                'code' => 'FIRST_STEP',
                'name' => 'First Step',
                'name_marathi' => 'पहिले पाऊल',
                'description' => 'Complete the first learning activity.',
                'description_marathi' => 'पहिली अध्ययन कृती पूर्ण करा.',
                'criteria' => ['activity_count' => 1],
                'xp_bonus' => 10,
            ]);
            $additionSkill = Skill::query()->where('code', 'ADDITION')->firstOrFail();
            $wordRecognitionSkill = Skill::query()->where('code', 'WORD_RECOGNITION')->firstOrFail();

            foreach ([
                [
                    'subject_id' => $marathi->id,
                    'skill_id' => $wordRecognitionSkill->id,
                    'code' => 'WORD_FRIEND',
                    'name' => 'Word Friend',
                    'name_marathi' => 'शब्दमित्र',
                    'description_marathi' => 'शब्द ओळख सराव तीन वेळा पूर्ण कर.',
                    'criteria' => ['practice_count' => 3],
                    'xp_bonus' => 15,
                    'rarity' => 'common',
                ],
                [
                    'subject_id' => $mathematics->id,
                    'code' => 'NUMBER_FRIEND',
                    'name' => 'Number Friend',
                    'name_marathi' => 'संख्यामित्र',
                    'description_marathi' => 'गणिताच्या पाच अध्ययन कृती पूर्ण कर.',
                    'criteria' => ['activity_count' => 5],
                    'xp_bonus' => 20,
                    'rarity' => 'common',
                ],
                [
                    'subject_id' => $mathematics->id,
                    'skill_id' => $additionSkill->id,
                    'code' => 'ADDITION_STAR',
                    'name' => 'Addition Star',
                    'name_marathi' => 'बेरीज स्टार',
                    'description_marathi' => 'बेरीज सराव तीन वेळा पूर्ण कर.',
                    'criteria' => ['practice_count' => 3],
                    'xp_bonus' => 20,
                    'rarity' => 'uncommon',
                ],
                [
                    'code' => 'CONSISTENCY_3',
                    'name' => 'Consistency Star',
                    'name_marathi' => 'सातत्यवीर',
                    'description_marathi' => 'सलग तीन दिवस अध्ययन कर.',
                    'criteria' => ['streak_days' => 3],
                    'xp_bonus' => 20,
                    'rarity' => 'uncommon',
                ],
                [
                    'code' => 'EFFORT_HERO',
                    'name' => 'Effort Hero',
                    'name_marathi' => 'प्रयत्नवीर',
                    'description_marathi' => 'पाच अध्ययन कृती पूर्ण कर.',
                    'criteria' => ['activity_count' => 5],
                    'xp_bonus' => 20,
                    'rarity' => 'common',
                ],
                [
                    'code' => 'PROGRESS_HERO',
                    'name' => 'Progress Hero',
                    'name_marathi' => 'प्रगतीवीर',
                    'description_marathi' => '१०० XP मिळव.',
                    'criteria' => ['xp' => 100],
                    'xp_bonus' => 25,
                    'rarity' => 'uncommon',
                ],
                [
                    'code' => 'PRACTICE_CHAMPION',
                    'name' => 'Practice Champion',
                    'name_marathi' => 'सराव चॅम्पियन',
                    'description_marathi' => 'दहा सराव कृती पूर्ण कर.',
                    'criteria' => ['practice_count' => 10],
                    'xp_bonus' => 30,
                    'rarity' => 'rare',
                ],
            ] as $badge) {
                Badge::query()->create($badge);
            }

            $this->createHolisticFramework();

            $students->each(fn (Student $student) => $student->skillProgress()->createMany(
                Skill::query()->get()->map(fn (Skill $skill) => [
                    'skill_id' => $skill->id,
                    'academic_year_id' => $academicYear->id,
                ])->all()
            ));
        });
    }

    private function createHolisticFramework(): void
    {
        $ratingScale = [
            1 => ['en' => 'Beginning', 'mr' => 'सुरुवात'],
            2 => ['en' => 'Developing', 'mr' => 'विकसनशील'],
            3 => ['en' => 'Progressing', 'mr' => 'प्रगतीशील'],
            4 => ['en' => 'Proficient', 'mr' => 'निपुण'],
            5 => ['en' => 'Advanced', 'mr' => 'प्रगत'],
        ];
        $domains = [
            [
                'code' => 'ACADEMIC',
                'name' => 'Academic development',
                'name_marathi' => 'शैक्षणिक विकास',
                'indicators' => [
                    ['READING', 'Reading', 'वाचन'],
                    ['WRITING', 'Writing', 'लेखन'],
                    ['NUMERACY', 'Numeracy', 'संख्याज्ञान'],
                    ['CONCEPT_UNDERSTANDING', 'Concept understanding', 'संकल्पना समज'],
                    ['PROBLEM_SOLVING', 'Problem solving', 'समस्या निराकरण'],
                ],
            ],
            [
                'code' => 'LEARNING_BEHAVIOUR',
                'name' => 'Learning behaviour',
                'name_marathi' => 'अध्ययन वर्तन',
                'indicators' => [
                    ['PARTICIPATION', 'Participation', 'सहभाग'],
                    ['REGULARITY', 'Regularity', 'नियमितता'],
                    ['TASK_COMPLETION', 'Task completion', 'कार्य पूर्णता'],
                    ['SELF_LEARNING', 'Self-learning', 'स्वयंअध्ययन'],
                    ['PERSISTENCE', 'Persistence', 'चिकाटी'],
                ],
            ],
            [
                'code' => 'SOCIAL',
                'name' => 'Social development',
                'name_marathi' => 'सामाजिक विकास',
                'indicators' => [
                    ['TEAMWORK', 'Teamwork', 'संघकार्य'],
                    ['COMMUNICATION', 'Communication', 'संवाद'],
                    ['COOPERATION', 'Cooperation', 'सहकार्य'],
                    ['HELPING_OTHERS', 'Helping others', 'इतरांना मदत'],
                ],
            ],
            [
                'code' => 'PERSONAL',
                'name' => 'Personal development',
                'name_marathi' => 'वैयक्तिक विकास',
                'indicators' => [
                    ['CONFIDENCE', 'Confidence', 'आत्मविश्वास'],
                    ['RESPONSIBILITY', 'Responsibility', 'जबाबदारी'],
                    ['SELF_EXPRESSION', 'Self-expression', 'स्व-अभिव्यक्ती'],
                    ['INTEREST', 'Interest', 'आवड'],
                    ['CREATIVITY', 'Creativity', 'सर्जनशीलता'],
                ],
            ],
            [
                'code' => 'DIGITAL_LEARNING',
                'name' => 'Digital learning',
                'name_marathi' => 'डिजिटल अध्ययन',
                'indicators' => [
                    ['GAME_PARTICIPATION', 'Game participation', 'खेळ सहभाग'],
                    ['MATHEMATICS_PRACTICE', 'Mathematics practice', 'गणित सराव'],
                    ['SIMULATION_PARTICIPATION', 'Simulation participation', 'अनुकरण सहभाग'],
                    ['INDEPENDENT_LEARNING', 'Independent learning', 'स्वतंत्र अध्ययन'],
                ],
            ],
        ];

        foreach ($domains as $domainIndex => $definition) {
            $domain = HolisticDomain::query()->create([
                'code' => $definition['code'],
                'name' => $definition['name'],
                'name_marathi' => $definition['name_marathi'],
                'sort_order' => $domainIndex + 1,
            ]);

            foreach ($definition['indicators'] as $indicatorIndex => [$code, $name, $nameMarathi]) {
                $domain->indicators()->create([
                    'code' => $code,
                    'name' => $name,
                    'name_marathi' => $nameMarathi,
                    'rating_scale' => $ratingScale,
                    'sort_order' => $indicatorIndex + 1,
                ]);
            }
        }
    }

    private function createRecommendationRules(): void
    {
        foreach ([
            [
                'code' => 'RED_LOW_ACCURACY',
                'title' => 'Very low accuracy',
                'title_marathi' => 'अतिशय कमी अचूकता',
                'signal' => 'accuracy',
                'operator' => 'lt',
                'threshold' => 50,
                'risk_level' => 'red',
                'minimum_events' => 3,
                'sort_order' => 10,
            ],
            [
                'code' => 'RED_LOW_MASTERY',
                'title' => 'Very low mastery',
                'title_marathi' => 'अतिशय कमी प्रभुत्व',
                'signal' => 'mastery',
                'operator' => 'lt',
                'threshold' => 40,
                'risk_level' => 'red',
                'minimum_events' => 3,
                'sort_order' => 20,
            ],
            [
                'code' => 'RED_REPEATED_ERROR',
                'title' => 'Repeated misconception',
                'title_marathi' => 'वारंवार होणारी संकल्पना चूक',
                'signal' => 'repeated_errors',
                'operator' => 'gte',
                'threshold' => 3,
                'risk_level' => 'red',
                'minimum_events' => 3,
                'sort_order' => 30,
            ],
            [
                'code' => 'RED_DECLINING_TREND',
                'title' => 'Declining recent performance',
                'title_marathi' => 'घटणारी अलीकडील कामगिरी',
                'signal' => 'recent_trend',
                'operator' => 'lte',
                'threshold' => -20,
                'risk_level' => 'red',
                'minimum_events' => 6,
                'sort_order' => 40,
            ],
            [
                'code' => 'YELLOW_LOW_ACCURACY',
                'title' => 'Accuracy needs practice',
                'title_marathi' => 'अचूकतेसाठी अधिक सराव',
                'signal' => 'accuracy',
                'operator' => 'lt',
                'threshold' => 70,
                'risk_level' => 'yellow',
                'minimum_events' => 2,
                'sort_order' => 100,
            ],
            [
                'code' => 'YELLOW_LOW_MASTERY',
                'title' => 'Mastery needs practice',
                'title_marathi' => 'प्रभुत्वासाठी अधिक सराव',
                'signal' => 'mastery',
                'operator' => 'lt',
                'threshold' => 70,
                'risk_level' => 'yellow',
                'minimum_events' => 1,
                'sort_order' => 110,
            ],
            [
                'code' => 'YELLOW_LOW_FREQUENCY',
                'title' => 'Practice frequency is low',
                'title_marathi' => 'सरावाची वारंवारता कमी',
                'signal' => 'practice_frequency',
                'operator' => 'lt',
                'threshold' => 2,
                'risk_level' => 'yellow',
                'minimum_events' => 3,
                'sort_order' => 120,
            ],
        ] as $rule) {
            RecommendationRule::query()->create([
                ...$rule,
                'guidance' => [],
                'is_active' => true,
            ]);
        }
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

    private function createDemoGames(User $creator): void
    {
        $letterSkill = Skill::query()->where('code', 'LETTER_RECOGNITION')->firstOrFail();
        $numberSkill = Skill::query()->where('code', 'NUMBER_RECOGNITION')->firstOrFail();
        $levelDefinitions = [
            [
                'level' => 1,
                'name' => 'Foundation',
                'name_marathi' => 'पायाभूत',
                'difficulty' => 1,
                'configuration' => [
                    'question_count' => 6,
                    'choice_count' => 3,
                    'item_count' => 6,
                    'lives' => 3,
                    'response_time_seconds' => 12,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 420,
                'time_limit_seconds' => 90,
            ],
            [
                'level' => 2,
                'name' => 'Growing',
                'name_marathi' => 'प्रगती',
                'difficulty' => 2,
                'configuration' => [
                    'question_count' => 8,
                    'choice_count' => 4,
                    'item_count' => 10,
                    'lives' => 3,
                    'response_time_seconds' => 10,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 560,
                'time_limit_seconds' => 100,
            ],
            [
                'level' => 3,
                'name' => 'Challenge',
                'name_marathi' => 'आव्हान',
                'difficulty' => 3,
                'configuration' => [
                    'question_count' => 10,
                    'choice_count' => 4,
                    'item_count' => 20,
                    'lives' => 3,
                    'response_time_seconds' => 8,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 700,
                'time_limit_seconds' => 110,
            ],
        ];
        $letters = collect([
            'अ', 'आ', 'इ', 'ई', 'उ', 'ऊ', 'ए', 'ऐ', 'ओ', 'औ',
            'क', 'ख', 'ग', 'घ', 'च', 'ज', 'ट', 'त', 'प', 'म',
        ])->map(fn (string $letter): array => ['value' => $letter, 'label' => $letter])->all();
        $numbers = collect(range(1, 20))->map(function (int $number): array {
            $label = strtr((string) $number, [
                '0' => '०',
                '1' => '१',
                '2' => '२',
                '3' => '३',
                '4' => '४',
                '5' => '५',
                '6' => '६',
                '7' => '७',
                '8' => '८',
                '9' => '९',
            ]);

            return ['value' => (string) $number, 'label' => $label];
        })->all();
        $games = [
            [
                'skill' => $letterSkill,
                'attributes' => [
                    'created_by' => $creator->id,
                    'code' => 'AKSHAR_PAKDA',
                    'engine_key' => 'catch',
                    'title' => 'Catch the Letter',
                    'title_marathi' => 'अक्षर पकडा',
                    'description' => 'Find and catch the requested Marathi letter.',
                    'description_marathi' => 'दिलेल्या मराठी अक्षराचे कार्ड ओळखा आणि पकडा.',
                    'configuration' => [
                        'icon' => 'अ',
                        'prompt' => 'Catch the letter :target',
                        'prompt_marathi' => ':target हे अक्षर पकडा',
                        'items' => $letters,
                        'visual_theme' => 'marathi_letters',
                        'sound_hook' => 'positive_tone',
                    ],
                    'status' => 'published',
                ],
            ],
            [
                'skill' => $numberSkill,
                'attributes' => [
                    'created_by' => $creator->id,
                    'code' => 'NUMBER_CATCH',
                    'engine_key' => 'catch',
                    'title' => 'Number Catch',
                    'title_marathi' => 'अंक पकडा',
                    'description' => 'Find and catch the requested number.',
                    'description_marathi' => 'दिलेला अंक ओळखा आणि त्याचे कार्ड पकडा.',
                    'configuration' => [
                        'icon' => '१२३',
                        'prompt' => 'Catch the number :target',
                        'prompt_marathi' => ':target हा अंक पकडा',
                        'items' => $numbers,
                        'visual_theme' => 'numbers',
                        'sound_hook' => 'positive_tone',
                    ],
                    'status' => 'published',
                ],
            ],
        ];
        $games = [
            ...$games,
            ...$this->mathematicsGameDefinitions(),
            ...$this->marathiGameDefinitions(),
        ];

        foreach ($games as $definition) {
            $definition['attributes']['created_by'] = $creator->id;
            $game = Game::query()->create($definition['attributes']);
            $game->levels()->createMany($levelDefinitions);

            if (isset($definition['skill'])) {
                $game->skills()->attach($definition['skill']->id, ['weight' => 1]);

                continue;
            }

            $skills = Skill::query()
                ->whereIn('code', array_keys($definition['skills']))
                ->get()
                ->keyBy('code');
            $game->skills()->attach(
                collect($definition['skills'])
                    ->mapWithKeys(fn (int|float $weight, string $code): array => [
                        $skills->get($code)->id => ['weight' => $weight],
                    ])
                    ->all(),
            );
        }
    }

    private function createDemoSimulations(User $creator): void
    {
        foreach ($this->simulationDefinitions() as $definition) {
            $simulation = Simulation::query()->create([
                'created_by' => $creator->id,
                'code' => $definition['code'],
                'engine_key' => $definition['engine_key'],
                'title' => $definition['title'],
                'title_marathi' => $definition['title_marathi'],
                'description' => $definition['description'],
                'description_marathi' => $definition['description_marathi'],
                'configuration' => [
                    'icon' => $definition['icon'],
                    'challenge_count' => 5,
                    'max_attempts' => 3,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 5,
                    ...($definition['configuration'] ?? []),
                ],
                'status' => 'published',
            ]);
            $skills = Skill::query()
                ->whereIn('code', array_keys($definition['skills']))
                ->get()
                ->keyBy('code');
            $simulation->skills()->attach(
                collect($definition['skills'])
                    ->mapWithKeys(fn (int|float $weight, string $code): array => [
                        $skills->get($code)->id => ['weight' => $weight],
                    ])
                    ->all(),
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function simulationDefinitions(): array
    {
        return [
            $this->simulationDefinition(
                'NUMBER_LINE_SIMULATION',
                'number_line',
                'Number Line',
                'संख्यारेषा',
                'Move a marker forward and backward on a number line.',
                'खूण पुढे-मागे हलवून संख्यारेषा समजून घ्या.',
                '↔️',
                ['NUMBER_ORDERING' => 1],
            ),
            $this->simulationDefinition(
                'ADDITION_OBJECTS_SIMULATION',
                'addition_objects',
                'Addition with Objects',
                'वस्तूंसह बेरीज',
                'Combine objects to model addition.',
                'वस्तू एकत्र करून बेरीज तयार करा.',
                '🍎',
                ['ADDITION' => 1],
            ),
            $this->simulationDefinition(
                'SUBTRACTION_OBJECTS_SIMULATION',
                'subtraction_objects',
                'Subtraction with Objects',
                'वस्तूंसह वजाबाकी',
                'Remove objects to model subtraction.',
                'वस्तू कमी करून वजाबाकी तयार करा.',
                '🥭',
                ['SUBTRACTION' => 1],
            ),
            $this->simulationDefinition(
                'PLACE_VALUE_BLOCKS_SIMULATION',
                'place_value_blocks',
                'Place Value Blocks',
                'स्थानिक किंमत ठोकळे',
                'Build numbers with hundreds, tens and ones.',
                'शेकडा, दशक आणि एकक ठोकळ्यांनी संख्या तयार करा.',
                '🧱',
                ['PLACE_VALUE' => 1],
            ),
            $this->simulationDefinition(
                'MULTIPLICATION_ARRAYS_SIMULATION',
                'multiplication_arrays',
                'Multiplication Arrays',
                'गुणाकार मांडणी',
                'Arrange dots into rows and columns.',
                'ठिपके ओळी आणि स्तंभांत मांडून गुणाकार समजा.',
                '🔵',
                ['MULTIPLICATION' => 1],
            ),
            $this->simulationDefinition(
                'DIVISION_SHARING_SIMULATION',
                'division_sharing',
                'Division Sharing',
                'भागाकार वाटप',
                'Share objects equally into groups.',
                'वस्तू गटांत समान वाटून भागाकार समजा.',
                '🤲',
                ['DIVISION' => 1],
            ),
            $this->simulationDefinition(
                'FRACTION_PIZZA_SIMULATION',
                'fraction_pizza',
                'Fraction Pizza',
                'अपूर्णांक पिझ्झा',
                'Fill equal pizza parts to model a fraction.',
                'पिझ्झाचे समान भाग भरून अपूर्णांक तयार करा.',
                '🍕',
                ['FRACTIONS' => 1],
            ),
            $this->simulationDefinition(
                'MONEY_SIMULATION',
                'money',
                'Money',
                'पैशांची मांडणी',
                'Combine coins and notes to make an amount.',
                'नाणी व नोटा एकत्र करून रक्कम तयार करा.',
                '💰',
                ['MONEY' => 1],
            ),
            $this->simulationDefinition(
                'CLOCK_SIMULATION',
                'clock',
                'Clock',
                'घड्याळ',
                'Move clock hands to show a time.',
                'काटे हलवून घड्याळात वेळ दाखवा.',
                '🕒',
                ['TIME' => 1],
            ),
            $this->simulationDefinition(
                'MEASUREMENT_SIMULATION',
                'measurement',
                'Measurement',
                'मोजमाप',
                'Change a line to the requested length.',
                'रेषेची लांबी बदलून मोजमाप समजा.',
                '📏',
                ['MEASUREMENT' => 1],
            ),
            $this->simulationDefinition(
                'GEOMETRY_BUILDER_SIMULATION',
                'geometry_builder',
                'Geometry Builder',
                'आकार बांधणी',
                'Drag shapes to rebuild a pattern.',
                'आकार ओढून दिलेला क्रम पुन्हा तयार करा.',
                '🔺',
                ['GEOMETRIC_SHAPES' => 1],
            ),
            $this->tokenSimulationDefinition(
                'LETTER_JOINING_SIMULATION',
                'Letter Joining',
                'अक्षर जोडणी',
                'Join letters to build a Marathi word.',
                'अक्षरे जोडून मराठी शब्द तयार करा.',
                '🔤',
                ['LETTER_JOINING' => 1, 'WORD_FORMATION' => 1],
                [
                    $this->simulationChallenge('Build the word lotus.', 'कमळ हा शब्द तयार करा.', ['क', 'म', 'ळ'], ['क', 'म', 'ळ', 'र']),
                    $this->simulationChallenge('Build the word house.', 'घर हा शब्द तयार करा.', ['घ', 'र'], ['घ', 'र', 'ग', 'ल']),
                    $this->simulationChallenge('Build the word fruit.', 'फळ हा शब्द तयार करा.', ['फ', 'ळ'], ['फ', 'ळ', 'प', 'ल']),
                ],
            ),
            $this->tokenSimulationDefinition(
                'MATRA_CHANGE_SIMULATION',
                'Change the Vowel Mark',
                'मात्रा बदल',
                'Move vowel marks to create the requested word.',
                'मात्रा हलवून दिलेला शब्द तयार करा.',
                '✍️',
                ['VOWEL_MARKS' => 1, 'WORD_FORMATION' => 1],
                [
                    $this->simulationChallenge('Build the word peacock.', 'मोर हा शब्द तयार करा.', ['म', 'ो', 'र'], ['म', 'ा', 'ो', 'र'], '🦚'),
                    $this->simulationChallenge('Build the word flower.', 'फूल हा शब्द तयार करा.', ['फ', 'ू', 'ल'], ['फ', 'ु', 'ू', 'ल'], '🌼'),
                    $this->simulationChallenge('Build the word mango.', 'आंबा हा शब्द तयार करा.', ['आं', 'बा'], ['आ', 'आं', 'बा', 'ब'], '🥭'),
                ],
            ),
            $this->tokenSimulationDefinition(
                'BARAKHADI_BUILDER_SIMULATION',
                'Barakhadi Builder',
                'बाराखडी बांधणी',
                'Combine a consonant and vowel mark.',
                'व्यंजन आणि मात्रा जोडून अक्षर तयार करा.',
                'क',
                ['BARAKHADI' => 1, 'VOWEL_MARKS' => 1],
                [
                    $this->simulationChallenge('Build kaa.', 'का तयार करा.', ['क', 'ा'], ['क', 'ा', 'ि', 'ी']),
                    $this->simulationChallenge('Build kee.', 'की तयार करा.', ['क', 'ी'], ['क', 'ि', 'ी', 'ु']),
                    $this->simulationChallenge('Build koo.', 'कू तयार करा.', ['क', 'ू'], ['क', 'ु', 'ू', 'े']),
                ],
            ),
            $this->tokenSimulationDefinition(
                'WORD_BUILDING_SIMULATION',
                'Word Building',
                'शब्द बांधणी',
                'Arrange syllables to build a word.',
                'अक्षरगट योग्य क्रमाने लावून शब्द बांधा.',
                '🧱',
                ['WORD_FORMATION' => 1, 'WORD_RECOGNITION' => 1],
                [
                    $this->simulationChallenge('Build the word school.', 'शाळा हा शब्द बांधा.', ['शा', 'ळा'], ['शा', 'ळा', 'ला']),
                    $this->simulationChallenge('Build the word butterfly.', 'फुलपाखरू हा शब्द बांधा.', ['फुल', 'पा', 'खरू'], ['फुल', 'पा', 'खरू', 'घर']),
                    $this->simulationChallenge('Build the word rainbow.', 'इंद्रधनुष्य हा शब्द बांधा.', ['इंद्र', 'धनु', 'ष्य'], ['इंद्र', 'धनु', 'ष्य', 'सूर्य']),
                ],
            ),
            $this->tokenSimulationDefinition(
                'SENTENCE_BUILDING_SIMULATION',
                'Sentence Building',
                'वाक्य बांधणी',
                'Arrange words to build a meaningful sentence.',
                'शब्द योग्य क्रमाने लावून अर्थपूर्ण वाक्य बांधा.',
                '📝',
                ['SENTENCE_FORMATION' => 1, 'SENTENCE_READING' => 1],
                [
                    $this->simulationChallenge('Build the sentence.', 'मी शाळेत जातो. हे वाक्य बांधा.', ['मी', 'शाळेत', 'जातो'], ['जातो', 'मी', 'खेळतो', 'शाळेत']),
                    $this->simulationChallenge('Build the sentence.', 'पक्षी आकाशात उडतो. हे वाक्य बांधा.', ['पक्षी', 'आकाशात', 'उडतो'], ['उडतो', 'पक्षी', 'आकाशात', 'पाणी']),
                    $this->simulationChallenge('Build the sentence.', 'आई गोष्ट सांगते. हे वाक्य बांधा.', ['आई', 'गोष्ट', 'सांगते'], ['गोष्ट', 'आई', 'सांगते', 'वाचतो']),
                ],
            ),
            $this->tokenSimulationDefinition(
                'PICTURE_SENTENCE_SIMULATION',
                'Picture to Sentence',
                'चित्रातून वाक्य तयार करा',
                'Use word tiles to describe a picture.',
                'चित्र पाहून शब्दफलकांनी वाक्य तयार करा.',
                '🖼️',
                ['SENTENCE_FORMATION' => 1, 'COMPREHENSION' => 1],
                [
                    $this->simulationChallenge('Describe the picture.', 'चित्र पाहून वाक्य तयार करा.', ['मुलगा', 'चेंडू', 'खेळतो'], ['चेंडू', 'मुलगा', 'खेळतो', 'वाचतो'], '👦 ⚽'),
                    $this->simulationChallenge('Describe the picture.', 'चित्र पाहून वाक्य तयार करा.', ['मुलगी', 'पुस्तक', 'वाचते'], ['वाचते', 'मुलगी', 'पुस्तक', 'धावते'], '👧 📖'),
                    $this->simulationChallenge('Describe the picture.', 'चित्र पाहून वाक्य तयार करा.', ['गाय', 'गवत', 'खाते'], ['गवत', 'गाय', 'खाते', 'उडते'], '🐄 🌿'),
                ],
            ),
        ];
    }

    /**
     * @param  array<string, int|float>  $skills
     * @return array<string, mixed>
     */
    private function simulationDefinition(
        string $code,
        string $engineKey,
        string $title,
        string $titleMarathi,
        string $description,
        string $descriptionMarathi,
        string $icon,
        array $skills,
        array $configuration = [],
    ): array {
        return [
            'code' => $code,
            'engine_key' => $engineKey,
            'title' => $title,
            'title_marathi' => $titleMarathi,
            'description' => $description,
            'description_marathi' => $descriptionMarathi,
            'icon' => $icon,
            'skills' => $skills,
            'configuration' => $configuration,
        ];
    }

    /**
     * @param  array<string, int|float>  $skills
     * @param  list<array<string, mixed>>  $challenges
     * @return array<string, mixed>
     */
    private function tokenSimulationDefinition(
        string $code,
        string $title,
        string $titleMarathi,
        string $description,
        string $descriptionMarathi,
        string $icon,
        array $skills,
        array $challenges,
    ): array {
        return $this->simulationDefinition(
            $code,
            'token_builder',
            $title,
            $titleMarathi,
            $description,
            $descriptionMarathi,
            $icon,
            $skills,
            ['challenges' => $challenges],
        );
    }

    /**
     * @param  list<string>  $answer
     * @param  list<string>  $tokens
     * @return array<string, mixed>
     */
    private function simulationChallenge(
        string $prompt,
        string $promptMarathi,
        array $answer,
        array $tokens,
        ?string $visual = null,
    ): array {
        return compact('prompt', 'answer', 'tokens', 'visual') + [
            'prompt_marathi' => $promptMarathi,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mathematicsGameDefinitions(): array
    {
        return [
            $this->gameDefinition(
                'NUMBER_TRAIN',
                'number_sequence',
                'Number Train',
                'अंकगाडी',
                'Complete the number sequence.',
                'अंकांचा योग्य क्रम पूर्ण करा.',
                '🚂',
                ['NUMBER_ORDERING' => 1],
            ),
            $this->gameDefinition(
                'GREATER_OR_SMALLER',
                'number_comparison',
                'Greater or Smaller',
                'मोठा की लहान',
                'Compare two numbers.',
                'दोन अंकांची तुलना करा.',
                '⚖️',
                ['NUMBER_COMPARISON' => 1],
            ),
            $this->gameDefinition(
                'PLACE_VALUE_HOUSE',
                'place_value',
                'Place Value House',
                'स्थानिक किंमत घर',
                'Find the place value of a digit.',
                'अंकाची स्थानिक किंमत ओळखा.',
                '🏠',
                ['PLACE_VALUE' => 1],
            ),
            $this->gameDefinition(
                'NUMBER_LINE_JUMP',
                'number_line',
                'Number Line Jump',
                'संख्यारेषेवर उडी',
                'Jump forward on a number line.',
                'संख्यारेषेवर योग्य उडी मारा.',
                '🦘',
                ['NUMBER_ORDERING' => 1, 'ADDITION' => 1],
            ),
            $this->gameDefinition(
                'ADDITION_ADVENTURE',
                'addition',
                'Addition Adventure',
                'बेरीज सफर',
                'Solve addition challenges.',
                'बेरीज सोडवून सफर पूर्ण करा.',
                '➕',
                ['ADDITION' => 1],
            ),
            $this->gameDefinition(
                'SUBTRACTION_ADVENTURE',
                'subtraction',
                'Subtraction Adventure',
                'वजाबाकी सफर',
                'Solve subtraction challenges.',
                'वजाबाकी सोडवून सफर पूर्ण करा.',
                '➖',
                ['SUBTRACTION' => 1],
            ),
            $this->gameDefinition(
                'FRACTION_PIZZA',
                'fraction',
                'Fraction Pizza',
                'अपूर्णांक पिझ्झा',
                'Match equal parts with a fraction.',
                'समान भागांचा योग्य अपूर्णांक निवडा.',
                '🍕',
                ['FRACTIONS' => 1],
            ),
            $this->gameDefinition(
                'SHOPPING_GAME',
                'shopping',
                'Shopping Game',
                'खरेदीचा खेळ',
                'Calculate a shopping total.',
                'वस्तूंची एकूण किंमत मोजा.',
                '🛒',
                ['MONEY' => 1, 'MULTIPLICATION' => 1],
            ),
            $this->gameDefinition(
                'MULTIPLICATION_SPACE_MISSION',
                'multiplication',
                'Multiplication Space Mission',
                'गुणाकार अंतराळ मोहीम',
                'Solve multiplication facts to travel through space.',
                'गुणाकार सोडवून अंतराळ मोहीम पूर्ण करा.',
                '🚀',
                ['MULTIPLICATION' => 1],
            ),
            $this->gameDefinition(
                'DIVISION_SHARING_GAME',
                'division',
                'Division Sharing Game',
                'भागाकार वाटप खेळ',
                'Share objects equally.',
                'वस्तूंचे समान वाटप करा.',
                '🤝',
                ['DIVISION' => 1],
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function marathiGameDefinitions(): array
    {
        return [
            $this->choiceBankGameDefinition(
                'MATRA_BALLOONS',
                'Vowel Mark Balloons',
                'मात्रा फुगे',
                'Choose the vowel mark that completes the word.',
                'शब्द पूर्ण करणारी योग्य मात्रा निवडा.',
                '🎈',
                ['VOWEL_MARKS' => 1],
                [
                    $this->gameQuestion('Complete the word m_r.', 'म_र हा शब्द पूर्ण करा.', 'ो', ['ा', 'ि', 'ु', 'ो']),
                    $this->gameQuestion('Complete the word ph_l.', 'फ_ल हा शब्द पूर्ण करा.', 'ू', ['ा', 'ी', 'ु', 'ू']),
                    $this->gameQuestion('Complete the word d_dh.', 'द_ध हा शब्द पूर्ण करा.', 'ू', ['ा', 'ि', 'ु', 'ू']),
                    $this->gameQuestion('Complete the word n_v.', 'न_व हा शब्द पूर्ण करा.', 'ा', ['ा', 'ि', 'ी', 'ु']),
                    $this->gameQuestion('Complete the word m_sa.', 'म_सा हा शब्द पूर्ण करा.', 'ा', ['ा', 'ि', 'ु', 'े']),
                    $this->gameQuestion('Complete the word d_va.', 'द_वा हा शब्द पूर्ण करा.', 'ि', ['ा', 'ि', 'ी', 'ु']),
                    $this->gameQuestion('Complete the word s_rya.', 'स_र्य हा शब्द पूर्ण करा.', 'ू', ['ा', 'ि', 'ु', 'ू']),
                    $this->gameQuestion('Complete the word k_li.', 'क_ळी हा शब्द पूर्ण करा.', 'े', ['ा', 'ि', 'ु', 'े']),
                    $this->gameQuestion('Complete the word m_laga.', 'म_लगा हा शब्द पूर्ण करा.', 'ु', ['ा', 'ि', 'ु', 'ू']),
                    $this->gameQuestion('Complete the word sh_la.', 'श_ळा हा शब्द पूर्ण करा.', 'ा', ['ा', 'ि', 'ु', 'े']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'BUILD_THE_WORD',
                'Build the Word',
                'शब्द तयार करा',
                'Join letters to make a meaningful word.',
                'अक्षरे जोडून अर्थपूर्ण शब्द तयार करा.',
                '🧩',
                ['WORD_FORMATION' => 1, 'LETTER_JOINING' => 1],
                [
                    $this->gameQuestion('Join ka + ma + la.', 'क + म + ळ यांपासून शब्द तयार करा.', 'कमळ', ['कमळ', 'कळम', 'मळक', 'मकल']),
                    $this->gameQuestion('Join gha + ra.', 'घ + र यांपासून शब्द तयार करा.', 'घर', ['घर', 'रघ', 'घरं', 'गर']),
                    $this->gameQuestion('Join aa + i.', 'आ + ई यांपासून शब्द तयार करा.', 'आई', ['आई', 'इआ', 'आइ', 'ईआ']),
                    $this->gameQuestion('Join ba + sa.', 'ब + स यांपासून शब्द तयार करा.', 'बस', ['बस', 'सब', 'बसा', 'सबा']),
                    $this->gameQuestion('Join pha + la.', 'फ + ळ यांपासून शब्द तयार करा.', 'फळ', ['फळ', 'ळफ', 'फल', 'फाळ']),
                    $this->gameQuestion('Join ma + ra.', 'म + र यांपासून शब्द तयार करा.', 'मर', ['मर', 'रम', 'मार', 'मोर']),
                    $this->gameQuestion('Join sha + la.', 'शा + ळा यांपासून शब्द तयार करा.', 'शाळा', ['शाळा', 'शाला', 'ळाशा', 'शाळ']),
                    $this->gameQuestion('Join pa + kshi.', 'प + क्षी यांपासून शब्द तयार करा.', 'पक्षी', ['पक्षी', 'पक्ष', 'क्षीप', 'पकशी']),
                    $this->gameQuestion('Join cha + ndra.', 'चं + द्र यांपासून शब्द तयार करा.', 'चंद्र', ['चंद्र', 'चंदर', 'द्रचं', 'चांद्र']),
                    $this->gameQuestion('Join pu + sta + ka.', 'पु + स्त + क यांपासून शब्द तयार करा.', 'पुस्तक', ['पुस्तक', 'पुसतक', 'स्तकपु', 'पुस्तका']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'PICTURE_WORD_MATCH',
                'Picture Word Match',
                'चित्र-शब्द जुळवा',
                'Choose the word that matches the picture.',
                'चित्राला जुळणारा शब्द निवडा.',
                '🖼️',
                ['WORD_RECOGNITION' => 1, 'VOCABULARY' => 1],
                [
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'आंबा', ['आंबा', 'केळी', 'फूल', 'घर'], ['visual' => '🥭']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'केळी', ['आंबा', 'केळी', 'पान', 'बस'], ['visual' => '🍌']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'सफरचंद', ['फूल', 'सफरचंद', 'चेंडू', 'वही'], ['visual' => '🍎']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'घर', ['शाळा', 'घर', 'झाड', 'फळ'], ['visual' => '🏠']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'झाड', ['झाड', 'पुस्तक', 'मासा', 'चंद्र'], ['visual' => '🌳']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'मासा', ['पक्षी', 'मासा', 'मांजर', 'फूल'], ['visual' => '🐟']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'पक्षी', ['मासा', 'पक्षी', 'चेंडू', 'सूर्य'], ['visual' => '🐦']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'पुस्तक', ['वही', 'पुस्तक', 'पेन्सिल', 'पाटी'], ['visual' => '📘']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'चेंडू', ['चेंडू', 'छत्री', 'दप्तर', 'बूट'], ['visual' => '⚽']),
                    $this->gameQuestion('Choose the matching word.', 'चित्राला जुळणारा शब्द निवडा.', 'सूर्य', ['चंद्र', 'तारा', 'सूर्य', 'ढग'], ['visual' => '☀️']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'WORD_TRAIN',
                'Word Train',
                'शब्द ट्रेन',
                'Add a related word to the train.',
                'शब्दगाडीत योग्य संबंधित शब्द जोडा.',
                '🚂',
                ['WORD_READING' => 1, 'VOCABULARY' => 1],
                [
                    $this->gameQuestion('Add another fruit.', 'आंबा → केळी → सफरचंद → ?', 'द्राक्ष', ['द्राक्ष', 'गाजर', 'बस', 'घर']),
                    $this->gameQuestion('Add another colour.', 'लाल → निळा → हिरवा → ?', 'पिवळा', ['पिवळा', 'आंबा', 'पक्षी', 'वही']),
                    $this->gameQuestion('Add another animal.', 'गाय → मांजर → कुत्रा → ?', 'घोडा', ['घोडा', 'कमळ', 'पाऊस', 'घर']),
                    $this->gameQuestion('Add another body part.', 'हात → पाय → डोळा → ?', 'नाक', ['नाक', 'फळ', 'पुस्तक', 'बस']),
                    $this->gameQuestion('Add another school item.', 'वही → पुस्तक → पेन्सिल → ?', 'पाटी', ['पाटी', 'मासा', 'सूर्य', 'फूल']),
                    $this->gameQuestion('Add another vehicle.', 'बस → रेल्वे → सायकल → ?', 'कार', ['कार', 'झाड', 'चंद्र', 'द्राक्ष']),
                    $this->gameQuestion('Add another flower.', 'गुलाब → कमळ → जास्वंद → ?', 'मोगरा', ['मोगरा', 'गाजर', 'पाऊस', 'पक्षी']),
                    $this->gameQuestion('Add another vegetable.', 'बटाटा → कांदा → टोमॅटो → ?', 'गाजर', ['गाजर', 'केळी', 'वही', 'चेंडू']),
                    $this->gameQuestion('Add another day.', 'सोमवार → मंगळवार → बुधवार → ?', 'गुरुवार', ['गुरुवार', 'जानेवारी', 'सकाळ', 'उन्हाळा']),
                    $this->gameQuestion('Add another season.', 'उन्हाळा → पावसाळा → ?', 'हिवाळा', ['हिवाळा', 'सोमवार', 'सकाळ', 'नदी']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'SENTENCE_MATCH',
                'Sentence Match',
                'वाक्य जुळवा',
                'Choose the sentence that matches the picture.',
                'चित्राला जुळणारे वाक्य निवडा.',
                '💬',
                ['SENTENCE_READING' => 1, 'COMPREHENSION' => 1],
                [
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'मुलगा पुस्तक वाचतो.', ['मुलगा पुस्तक वाचतो.', 'मुलगा चेंडू खेळतो.', 'मुलगा झोपतो.', 'मुलगा धावतो.'], ['visual' => '👦📖']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'मुलगी सायकल चालवते.', ['मुलगी सायकल चालवते.', 'मुलगी जेवते.', 'मुलगी गाते.', 'मुलगी लिहिते.'], ['visual' => '👧🚲']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'मांजर दूध पिते.', ['मांजर दूध पिते.', 'मांजर उडते.', 'मांजर पुस्तक वाचते.', 'मांजर पोहते.'], ['visual' => '🐈🥛']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'पाऊस पडत आहे.', ['पाऊस पडत आहे.', 'ऊन पडले आहे.', 'बर्फ पडतो.', 'वारा थांबला आहे.'], ['visual' => '🌧️']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'पक्षी झाडावर बसला आहे.', ['पक्षी झाडावर बसला आहे.', 'मासा झाडावर आहे.', 'पक्षी पाण्यात आहे.', 'झाड उडत आहे.'], ['visual' => '🐦🌳']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'मुले शाळेत जातात.', ['मुले शाळेत जातात.', 'मुले बाजारात झोपतात.', 'मुले नदीत लिहितात.', 'मुले आकाशात उडतात.'], ['visual' => '🧒🧒🏫']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'आई स्वयंपाक करते.', ['आई स्वयंपाक करते.', 'आई सायकल दुरुस्त करते.', 'आई पोहते.', 'आई झोपली आहे.'], ['visual' => '👩🍲']),
                    $this->gameQuestion('Match the sentence.', 'चित्राला जुळणारे वाक्य निवडा.', 'शेतकरी शेतात काम करतो.', ['शेतकरी शेतात काम करतो.', 'शेतकरी विमान चालवतो.', 'शेतकरी वर्गात शिकवतो.', 'शेतकरी दुकानात झोपतो.'], ['visual' => '🧑‍🌾🌾']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'LISTEN_AND_SELECT',
                'Listen and Select',
                'ऐका आणि निवडा',
                'Listen and choose the word you hear.',
                'शब्द ऐका आणि योग्य शब्द निवडा.',
                '🔊',
                ['DICTATION' => 1, 'WORD_RECOGNITION' => 1],
                [
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'कमळ', ['कमळ', 'कपाट', 'कळम', 'कपाळ'], ['audio_text' => 'कमळ']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'आकाश', ['आकाश', 'आकार', 'आगार', 'आवाज'], ['audio_text' => 'आकाश']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'पुस्तक', ['पुस्तक', 'पुस्तिका', 'पुसतक', 'मस्तक'], ['audio_text' => 'पुस्तक']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'शाळा', ['शाळा', 'शाला', 'माळा', 'ताळा'], ['audio_text' => 'शाळा']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'फुलपाखरू', ['फुलपाखरू', 'फुलदाणी', 'फुलझाड', 'फळपाखरू'], ['audio_text' => 'फुलपाखरू']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'चिमणी', ['चिमणी', 'चांदणी', 'चिंचणी', 'चमचा'], ['audio_text' => 'चिमणी']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'पाऊस', ['पाऊस', 'पावस', 'पाउस', 'पायस'], ['audio_text' => 'पाऊस']),
                    $this->gameQuestion('Listen and select the word.', 'ऐका आणि योग्य शब्द निवडा.', 'सायकल', ['सायकल', 'सायंकाळ', 'साखळ', 'सागर'], ['audio_text' => 'सायकल']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'FIND_CORRECT_WORD',
                'Find the Correct Word',
                'योग्य शब्द शोधा',
                'Find the correctly written word.',
                'योग्य लिहिलेला शब्द शोधा.',
                '🔎',
                ['WORD_RECOGNITION' => 1, 'VOWEL_MARKS' => 1],
                [
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'पाऊस', ['पाऊस', 'पाउस', 'पावूस', 'पाऊश']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'शाळा', ['शाळा', 'शाला', 'शाळ', 'शाळॉ']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'पुस्तक', ['पुस्तक', 'पूसतक', 'पुस्ताक', 'पुस्तक्']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'केळी', ['केळी', 'केलि', 'केळीं', 'कळी']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'सूर्य', ['सूर्य', 'सुर्य', 'सूऱ्य', 'सूर्ये']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'मुलगी', ['मुलगी', 'मूलगी', 'मुलगि', 'मूलगि']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'चिमणी', ['चिमणी', 'चिमनी', 'चीमणी', 'चिमणि']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'फुलपाखरू', ['फुलपाखरू', 'फूलपाखरु', 'फुलपाखरु', 'फुलपाखरुं']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'स्वच्छ', ['स्वच्छ', 'स्वछ', 'स्वच्छा', 'स्वचछ']),
                    $this->gameQuestion('Find the correct spelling.', 'योग्य शब्द शोधा.', 'मैदान', ['मैदान', 'मेदान', 'मैदाण', 'मैदानं']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'WORD_ORDER',
                'Word Order',
                'शब्द क्रम लावा',
                'Put the words in a meaningful order.',
                'शब्दांचा अर्थपूर्ण क्रम निवडा.',
                '🔢',
                ['SENTENCE_FORMATION' => 1],
                [
                    $this->gameQuestion('Order: school / goes / Raju / to', 'शब्द लावा: शाळेत / राजू / जातो', 'राजू शाळेत जातो.', ['राजू शाळेत जातो.', 'शाळेत जातो राजू.', 'जातो राजू शाळेत.', 'राजू जातो शाळेत.']),
                    $this->gameQuestion('Order: eats / mango / Sita', 'शब्द लावा: आंबा / सीता / खाते', 'सीता आंबा खाते.', ['सीता आंबा खाते.', 'आंबा खाते सीता.', 'खाते सीता आंबा.', 'सीता खाते आंबा.']),
                    $this->gameQuestion('Order: rises / sun / east', 'शब्द लावा: पूर्वेला / सूर्य / उगवतो', 'सूर्य पूर्वेला उगवतो.', ['सूर्य पूर्वेला उगवतो.', 'पूर्वेला उगवतो सूर्य.', 'उगवतो सूर्य पूर्वेला.', 'सूर्य उगवतो पूर्वेला.']),
                    $this->gameQuestion('Order: bird / flies / sky', 'शब्द लावा: आकाशात / पक्षी / उडतो', 'पक्षी आकाशात उडतो.', ['पक्षी आकाशात उडतो.', 'आकाशात उडतो पक्षी.', 'उडतो पक्षी आकाशात.', 'पक्षी उडतो आकाशात.']),
                    $this->gameQuestion('Order: mother / cooks / food', 'शब्द लावा: स्वयंपाक / आई / करते', 'आई स्वयंपाक करते.', ['आई स्वयंपाक करते.', 'स्वयंपाक करते आई.', 'करते आई स्वयंपाक.', 'आई करते स्वयंपाक.']),
                    $this->gameQuestion('Order: children / ground / play', 'शब्द लावा: मैदानात / मुले / खेळतात', 'मुले मैदानात खेळतात.', ['मुले मैदानात खेळतात.', 'मैदानात खेळतात मुले.', 'खेळतात मुले मैदानात.', 'मुले खेळतात मैदानात.']),
                    $this->gameQuestion('Order: water / river / flows', 'शब्द लावा: नदीतून / पाणी / वाहते', 'नदीतून पाणी वाहते.', ['नदीतून पाणी वाहते.', 'पाणी वाहते नदीतून.', 'वाहते नदीतून पाणी.', 'नदीतून वाहते पाणी.']),
                    $this->gameQuestion('Order: flower / garden / blooms', 'शब्द लावा: बागेत / फूल / उमलते', 'बागेत फूल उमलते.', ['बागेत फूल उमलते.', 'फूल उमलते बागेत.', 'उमलते बागेत फूल.', 'बागेत उमलते फूल.']),
                ],
            ),
            $this->choiceBankGameDefinition(
                'READING_CHALLENGE',
                'Reading Challenge',
                'वाचन आव्हान',
                'Read a short passage and answer.',
                'छोटा उतारा वाचा आणि उत्तर द्या.',
                '📖',
                ['PARAGRAPH_READING' => 1, 'COMPREHENSION' => 1],
                [
                    $this->gameQuestion('Where did Ravi go?', 'रवी कुठे गेला?', 'बागेत', ['बागेत', 'शाळेत', 'बाजारात', 'नदीवर'], ['context_marathi' => 'रवी सकाळी बागेत गेला. त्याने लाल फुले पाहिली.']),
                    $this->gameQuestion('What colour were the flowers?', 'फुले कोणत्या रंगाची होती?', 'लाल', ['लाल', 'निळी', 'पिवळी', 'पांढरी'], ['context_marathi' => 'रवी सकाळी बागेत गेला. त्याने लाल फुले पाहिली.']),
                    $this->gameQuestion('What does Mina like?', 'मीनाला काय आवडते?', 'पुस्तके वाचणे', ['पुस्तके वाचणे', 'पोहणे', 'स्वयंपाक', 'झोपणे'], ['context_marathi' => 'मीना रोज शाळेत जाते. तिला गोष्टींची पुस्तके वाचायला आवडतात.']),
                    $this->gameQuestion('When does Mina go to school?', 'मीना शाळेत कधी जाते?', 'रोज', ['रोज', 'रविवारी', 'रात्री', 'महिन्यातून एकदा'], ['context_marathi' => 'मीना रोज शाळेत जाते. तिला गोष्टींची पुस्तके वाचायला आवडतात.']),
                    $this->gameQuestion('Who worked in the field?', 'शेतात कोण काम करत होता?', 'शेतकरी', ['शेतकरी', 'डॉक्टर', 'शिक्षक', 'चालक'], ['context_marathi' => 'शेतकरी शेतात काम करत होता. काळे ढग आले आणि पाऊस सुरू झाला.']),
                    $this->gameQuestion('What started after clouds came?', 'ढग आल्यानंतर काय सुरू झाले?', 'पाऊस', ['पाऊस', 'ऊन', 'बर्फ', 'वाद्य'], ['context_marathi' => 'शेतकरी शेतात काम करत होता. काळे ढग आले आणि पाऊस सुरू झाला.']),
                    $this->gameQuestion('What did the children plant?', 'मुलांनी काय लावले?', 'रोप', ['रोप', 'दगड', 'खेळणे', 'पुस्तक'], ['context_marathi' => 'मुलांनी शाळेच्या अंगणात एक रोप लावले. त्यांनी रोज त्याला पाणी दिले.']),
                    $this->gameQuestion('What did children give the plant?', 'मुलांनी रोपाला काय दिले?', 'पाणी', ['पाणी', 'दूध', 'रंग', 'वाळू'], ['context_marathi' => 'मुलांनी शाळेच्या अंगणात एक रोप लावले. त्यांनी रोज त्याला पाणी दिले.']),
                ],
            ),
        ];
    }

    /**
     * @param  array<string, int|float>  $skills
     * @return array<string, mixed>
     */
    private function gameDefinition(
        string $code,
        string $engineKey,
        string $title,
        string $titleMarathi,
        string $description,
        string $descriptionMarathi,
        string $icon,
        array $skills,
    ): array {
        return [
            'skills' => $skills,
            'attributes' => [
                'created_by' => null,
                'code' => $code,
                'engine_key' => $engineKey,
                'title' => $title,
                'title_marathi' => $titleMarathi,
                'description' => $description,
                'description_marathi' => $descriptionMarathi,
                'configuration' => [
                    'icon' => $icon,
                    'visual_theme' => $engineKey,
                    'sound_hook' => 'positive_tone',
                ],
                'status' => 'published',
            ],
        ];
    }

    /**
     * @param  array<string, int|float>  $skills
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    private function choiceBankGameDefinition(
        string $code,
        string $title,
        string $titleMarathi,
        string $description,
        string $descriptionMarathi,
        string $icon,
        array $skills,
        array $questions,
    ): array {
        $definition = $this->gameDefinition(
            $code,
            'choice_bank',
            $title,
            $titleMarathi,
            $description,
            $descriptionMarathi,
            $icon,
            $skills,
        );
        $definition['attributes']['configuration']['questions'] = $questions;

        return $definition;
    }

    /**
     * @param  list<string>  $choices
     * @param  array<string, string>  $presentation
     * @return array<string, mixed>
     */
    private function gameQuestion(
        string $prompt,
        string $promptMarathi,
        string $answer,
        array $choices,
        array $presentation = [],
    ): array {
        return [
            'prompt' => $prompt,
            'prompt_marathi' => $promptMarathi,
            'answer' => $answer,
            'choices' => collect($choices)->map(fn (string $choice): array => [
                'value' => $choice,
                'label' => $choice,
            ])->all(),
            ...$presentation,
        ];
    }

    private function createDemoPracticeContent(User $creator): void
    {
        $vowels = Skill::query()->where('code', 'VOWELS')->firstOrFail();
        $vowelError = $vowels->errorTypes()->create([
            'code' => 'VOWEL_IDENTIFICATION',
            'name' => 'Vowel identification error',
            'name_marathi' => 'स्वर ओळख चूक',
            'description' => 'The student selected a consonant or an unrelated symbol.',
            'remediation' => ['hint' => 'Review अ, आ, इ, ई before trying again.'],
        ]);
        $vowelActivity = Activity::query()->create([
            'skill_id' => $vowels->id,
            'skill_level_id' => $vowels->levels()->where('level', 1)->value('id'),
            'created_by' => $creator->id,
            'code' => 'MARATHI_VOWELS_PRACTICE_1',
            'type' => 'practice',
            'title' => 'Recognize Marathi vowels',
            'title_marathi' => 'मराठी स्वर ओळखा',
            'instructions' => 'Choose or type the correct vowel.',
            'instructions_marathi' => 'योग्य स्वर निवडा किंवा लिहा.',
            'content' => ['body' => null, 'body_marathi' => null, 'examples' => ['अ, आ, इ, ई']],
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'max_score' => 10,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $vowelActivity->practiceActivity()->create([
            'question_count' => 3,
            'randomize_questions' => true,
            'show_feedback_immediately' => true,
            'configuration' => [
                'difficulty_up_accuracy' => 80,
                'remedial_accuracy' => 60,
                'minimum_difficulty' => 1,
                'maximum_difficulty' => 5,
            ],
        ]);
        $vowelQuestion = $vowelActivity->questions()->create([
            'skill_id' => $vowels->id,
            'error_type_id' => $vowelError->id,
            'created_by' => $creator->id,
            'type' => 'mcq',
            'prompt' => 'Which of these is a Marathi vowel?',
            'prompt_marathi' => 'यापैकी मराठी स्वर कोणता?',
            'correct_answer' => [],
            'explanation' => 'अ is a vowel.',
            'explanation_marathi' => 'अ हा स्वर आहे.',
            'difficulty' => 1,
            'marks' => 1,
            'is_active' => true,
        ]);
        $vowelQuestion->options()->createMany([
            ['label' => 'अ', 'label_marathi' => 'अ', 'is_correct' => true, 'sort_order' => 1],
            ['label' => 'क', 'label_marathi' => 'क', 'is_correct' => false, 'sort_order' => 2],
            ['label' => 'म', 'label_marathi' => 'म', 'is_correct' => false, 'sort_order' => 3],
        ]);
        foreach ([['आ', 'आ'], ['इ', 'इ']] as $index => [$answer, $marathiAnswer]) {
            $vowelActivity->questions()->create([
                'skill_id' => $vowels->id,
                'error_type_id' => $vowelError->id,
                'created_by' => $creator->id,
                'type' => 'text_input',
                'prompt' => $index === 0 ? 'Type the long A vowel.' : 'Type the short I vowel.',
                'prompt_marathi' => $index === 0 ? 'दीर्घ आ स्वर लिहा.' : 'ऱ्हस्व इ स्वर लिहा.',
                'correct_answer' => ['accepted' => [$answer, $marathiAnswer]],
                'explanation_marathi' => "योग्य उत्तर {$answer} आहे.",
                'difficulty' => 1,
                'marks' => 1,
                'is_active' => true,
            ]);
        }

        $addition = Skill::query()->where('code', 'ADDITION')->firstOrFail();
        $additionError = $addition->errorTypes()->create([
            'code' => 'ADDITION_FACT',
            'name' => 'Addition fact error',
            'name_marathi' => 'बेरीज तथ्य चूक',
            'description' => 'The student needs support combining small quantities.',
            'remediation' => ['hint' => 'Count both groups using objects or fingers.'],
        ]);
        $additionActivity = Activity::query()->create([
            'skill_id' => $addition->id,
            'skill_level_id' => $addition->levels()->where('level', 1)->value('id'),
            'created_by' => $creator->id,
            'code' => 'MATHEMATICS_ADDITION_PRACTICE_1',
            'type' => 'practice',
            'title' => 'Addition within ten',
            'title_marathi' => 'दहाच्या आतील बेरीज',
            'instructions' => 'Solve each addition question.',
            'instructions_marathi' => 'प्रत्येक बेरीज सोडवा.',
            'content' => ['body' => null, 'body_marathi' => null, 'examples' => ['2 + 3 = 5']],
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'max_score' => 10,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $additionActivity->practiceActivity()->create([
            'question_count' => 3,
            'randomize_questions' => true,
            'show_feedback_immediately' => true,
            'configuration' => [
                'difficulty_up_accuracy' => 80,
                'remedial_accuracy' => 60,
                'minimum_difficulty' => 1,
                'maximum_difficulty' => 5,
            ],
        ]);
        foreach ([[2, 3, 5], [4, 2, 6], [7, 1, 8]] as [$first, $second, $answer]) {
            $additionActivity->questions()->create([
                'skill_id' => $addition->id,
                'error_type_id' => $additionError->id,
                'created_by' => $creator->id,
                'type' => 'number_input',
                'prompt' => "What is {$first} + {$second}?",
                'prompt_marathi' => "{$first} + {$second} किती?",
                'correct_answer' => ['value' => $answer],
                'explanation' => "{$first} plus {$second} equals {$answer}.",
                'explanation_marathi' => "{$first} अधिक {$second} बरोबर {$answer}.",
                'difficulty' => 1,
                'marks' => 1,
                'is_active' => true,
            ]);
        }
    }

    private function createDemoAssessments(
        School $school,
        AcademicYear $academicYear,
        SchoolClass $schoolClass,
        User $creator,
    ): void {
        $assessmentDefinitions = [
            ['MARATHI', 'VOWELS', 'MARATHI_PRE_1', 'pre_test', 'Marathi vowel baseline', 'मराठी स्वर पूर्व चाचणी'],
            ['MATHEMATICS', 'ADDITION', 'MATH_ADDITION_PRE_1', 'pre_test', 'Addition baseline', 'बेरीज पूर्व चाचणी'],
            ['MATHEMATICS', 'ADDITION', 'MATH_ADDITION_POST_1', 'post_test', 'Addition post-test', 'बेरीज उत्तर चाचणी'],
        ];

        foreach ($assessmentDefinitions as [$subjectCode, $skillCode, $code, $type, $title, $titleMarathi]) {
            $subject = Subject::query()->where('code', $subjectCode)->firstOrFail();
            $skill = Skill::query()->whereBelongsTo($subject)->where('code', $skillCode)->firstOrFail();
            $questions = Question::query()
                ->whereBelongsTo($skill)
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(3)
                ->get();
            $test = Test::query()->create([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'school_class_id' => $schoolClass->id,
                'subject_id' => $subject->id,
                'created_by' => $creator->id,
                'code' => $code,
                'type' => $type,
                'title' => $title,
                'title_marathi' => $titleMarathi,
                'instructions' => 'Answer every question without help.',
                'instructions_marathi' => 'मदतीशिवाय प्रत्येक प्रश्न सोडवा.',
                'duration_minutes' => 15,
                'difficulty' => 1,
                'question_count' => $questions->count(),
                'max_attempts' => 1,
                'passing_score' => 60,
                'shuffle_questions' => false,
                'status' => 'published',
            ]);
            $test->questions()->attach($questions->mapWithKeys(
                fn (Question $question, int $index): array => [$question->id => [
                    'sort_order' => $index + 1,
                    'marks' => $question->marks,
                ]],
            ));
        }
    }
}
