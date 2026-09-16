<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Badge;
use App\Models\Division;
use App\Models\Game;
use App\Models\HolisticDomain;
use App\Models\LearningOutcome;
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
                ['SYNONYMS', 'Synonyms', 'समानार्थी शब्द'],
                ['ANTONYMS', 'Antonyms', 'विरुद्धार्थी शब्द'],
                ['GENDER_AND_NUMBER', 'Gender and number', 'लिंग आणि वचन'],
                ['PARTS_OF_SPEECH', 'Parts of speech', 'शब्दांच्या जाती'],
                ['TENSE', 'Tense', 'काळ'],
                ['IDIOMS_AND_PROVERBS', 'Idioms and proverbs', 'वाक्प्रचार आणि म्हणी'],
                ['PUNCTUATION', 'Punctuation', 'विरामचिन्हे'],
                ['POETRY_COMPREHENSION', 'Poetry comprehension', 'कविता आकलन'],
                ['CREATIVE_WRITING', 'Creative writing', 'सर्जनशील लेखन'],
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
                ['PATTERNS', 'Patterns', 'आकृतिबंध'],
                ['DECIMALS', 'Decimals', 'दशांश अपूर्णांक'],
                ['FACTORS_AND_MULTIPLES', 'Factors and multiples', 'विभाजक आणि पटी'],
                ['INTEGERS', 'Integers', 'पूर्णांक संख्या'],
                ['RATIO_AND_PROPORTION', 'Ratio and proportion', 'गुणोत्तर आणि प्रमाण'],
                ['PERCENTAGE', 'Percentage', 'शेकडेवारी'],
                ['ALGEBRA', 'Algebra', 'बीजगणित'],
                ['ANGLES', 'Angles', 'कोन'],
                ['PERIMETER_AND_AREA', 'Perimeter and area', 'परिमिती आणि क्षेत्रफळ'],
                ['DATA_HANDLING', 'Data handling', 'माहितीचे व्यवस्थापन'],
            ]);
            $this->createDemoGames($platformAdministrator);
            $this->createDemoSimulations($platformAdministrator);
            $this->createDemoPracticeContent($platformAdministrator);
            $this->createClass4LearningOutcomes($marathi, $mathematics);
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
                    'response_time_seconds' => 60,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 420,
                'time_limit_seconds' => 360,
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
                    'response_time_seconds' => 50,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 560,
                'time_limit_seconds' => 400,
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
                    'response_time_seconds' => 45,
                    'difficulty_up_accuracy' => 80,
                    'remedial_accuracy' => 50,
                    'minimum_difficulty' => 1,
                    'maximum_difficulty' => 3,
                ],
                'target_score' => 700,
                'time_limit_seconds' => 450,
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
            ...$this->upperPrimaryMathematicsGameDefinitions(),
            ...$this->marathiGameDefinitions(),
            ...$this->upperPrimaryMarathiGameDefinitions(),
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
    private function upperPrimaryMathematicsGameDefinitions(): array
    {
        return [
            $this->choiceBankGameDefinition(
                'PATTERN_CODE_BREAKER',
                'Pattern Code Breaker',
                'आकृतिबंध संकेतभेद',
                'Find the rule and complete number and shape patterns.',
                'नियम शोधून संख्या आणि आकारांचा आकृतिबंध पूर्ण करा.',
                '🧩',
                ['PATTERNS' => 1, 'NUMBER_ORDERING' => 0.5],
                [
                    $this->gameQuestion('Complete the pattern.', 'आकृतिबंध पूर्ण करा: ३, ६, ९, १२, ?', '१५', ['१४', '१५', '१६', '१८'], ['visual' => '३ → ६ → ९ → १२ → ?']),
                    $this->gameQuestion('Complete the pattern.', 'आकृतिबंध पूर्ण करा: २५, २०, १५, १०, ?', '५', ['०', '५', '८', '१५'], ['visual' => '२५ → २० → १५ → १० → ?']),
                    $this->gameQuestion('Which shape comes next?', 'पुढे कोणता आकार येईल? ○ △ ○ △ ?', '○', ['○', '△', '□', '◇'], ['visual' => '○  △  ○  △  ?']),
                    $this->gameQuestion('Find the missing number.', 'रिकामी जागा भरा: २, ४, ८, १६, ?', '३२', ['१८', '२४', '३०', '३२'], ['visual' => '× २ प्रत्येक वेळी']),
                ],
                ['grade_min' => 2, 'grade_max' => 5],
            ),
            $this->choiceBankGameDefinition(
                'DECIMAL_MARKET',
                'Decimal Market',
                'दशांश बाजार',
                'Read, compare and calculate decimal quantities.',
                'दशांश संख्या वाचा, तुलना करा आणि गणना करा.',
                '🧾',
                ['DECIMALS' => 1, 'MONEY' => 0.5],
                [
                    $this->gameQuestion('Choose the larger decimal.', 'मोठी दशांश संख्या निवडा.', '३.७५', ['३.७५', '३.५७', '३.०७', '३.२५'], ['visual' => '३.७५  ?  ३.५७']),
                    $this->gameQuestion('Add the prices.', '₹१२.५० + ₹७.२५ = ?', '₹१९.७५', ['₹१८.७५', '₹१९.२५', '₹१९.७५', '₹२०.७५'], ['visual' => '₹१२.५० + ₹७.२५']),
                    $this->gameQuestion('Write seven tenths as a decimal.', 'सात दशांश दशांश संख्येत लिहा.', '०.७', ['०.०७', '०.७', '७.०', '७०.०'], ['visual' => '७ / १०']),
                    $this->gameQuestion('Subtract the lengths.', '५.६ मी − २.४ मी = ?', '३.२ मी', ['२.२ मी', '३.२ मी', '३.६ मी', '४.२ मी'], ['visual' => '५.६ मी − २.४ मी']),
                ],
                ['grade_min' => 5, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'FACTOR_MULTIPLE_LAB',
                'Factor and Multiple Lab',
                'विभाजक-पटी प्रयोगशाळा',
                'Identify factors, multiples, prime numbers, HCF and LCM.',
                'विभाजक, पटी, मूळ संख्या, मसावी आणि लसावी ओळखा.',
                '🔬',
                ['FACTORS_AND_MULTIPLES' => 1],
                [
                    $this->gameQuestion('Which is a factor of २४?', '२४ चा विभाजक कोणता?', '६', ['५', '६', '७', '९'], ['visual' => '२४ = ६ × ४']),
                    $this->gameQuestion('Which number is prime?', 'मूळ संख्या कोणती?', '२९', ['२१', '२७', '२९', '३३'], ['visual' => 'फक्त १ आणि ती संख्या हे विभाजक']),
                    $this->gameQuestion('Find the HCF of १२ and १८.', '१२ आणि १८ चा मसावी शोधा.', '६', ['२', '३', '६', '९'], ['visual' => '१२ : १, २, ३, ४, ६, १२\n१८ : १, २, ३, ६, ९, १८']),
                    $this->gameQuestion('Find the LCM of ४ and ६.', '४ आणि ६ चा लसावी शोधा.', '१२', ['८', '१०', '१२', '२४'], ['visual' => '४ च्या पटी आणि ६ च्या पटी']),
                ],
                ['grade_min' => 5, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'INTEGER_ELEVATOR',
                'Integer Elevator',
                'पूर्णांक उद्वाहक',
                'Move above and below zero to solve integer problems.',
                'शून्याच्या वर-खाली जाऊन पूर्णांकांची उदाहरणे सोडवा.',
                '🛗',
                ['INTEGERS' => 1, 'NUMBER_ORDERING' => 0.5],
                [
                    $this->gameQuestion('Move ५ floors down from २.', '२ वरून ५ मजले खाली गेल्यावर कोणता पूर्णांक येईल?', '−३', ['−७', '−३', '३', '७'], ['visual' => '+३\n+२  ← सुरुवात\n+१\n ०\n−१\n−२\n−३']),
                    $this->gameQuestion('Choose the greater integer.', 'मोठा पूर्णांक निवडा.', '−२', ['−८', '−५', '−२', '−९'], ['visual' => '−८  −५  −२  −९']),
                    $this->gameQuestion('Calculate.', '−४ + ७ = ?', '३', ['−११', '−३', '३', '११'], ['visual' => '−४ पासून उजवीकडे ७ पावले']),
                    $this->gameQuestion('Calculate.', '५ − ९ = ?', '−४', ['−१४', '−४', '४', '१४'], ['visual' => '५ पासून डावीकडे ९ पावले']),
                ],
                ['grade_min' => 6, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'RATIO_RECIPE',
                'Ratio Recipe',
                'गुणोत्तर पाककृती',
                'Use ratios and proportions in practical situations.',
                'व्यवहारातील उदाहरणांत गुणोत्तर आणि प्रमाण वापरा.',
                '🥣',
                ['RATIO_AND_PROPORTION' => 1],
                [
                    $this->gameQuestion('Simplify the ratio ८:१२.', '८:१२ हे गुणोत्तर संक्षिप्त करा.', '२:३', ['१:२', '२:३', '३:४', '४:६'], ['visual' => '८ लाल : १२ निळे']),
                    $this->gameQuestion('A recipe uses २ cups of flour for १ cup of milk. How much flour for ३ cups of milk?', '१ वाटी दुधासाठी २ वाट्या पीठ लागते. ३ वाट्या दुधासाठी किती पीठ?', '६ वाट्या', ['३ वाट्या', '४ वाट्या', '५ वाट्या', '६ वाट्या'], ['visual' => 'पीठ : दूध = २ : १']),
                    $this->gameQuestion('Find the missing value.', '३:५ = १२:?', '२०', ['१५', '१८', '२०', '२४'], ['visual' => '३ × ४ : ५ × ४']),
                    $this->gameQuestion('Which ratio is equivalent to ४:६?', '४:६ च्या सममूल्य गुणोत्तराची निवड करा.', '१०:१५', ['६:८', '८:१०', '१०:१५', '१२:१५'], ['visual' => '४ : ६ = ?']),
                ],
                ['grade_min' => 6, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'PERCENTAGE_TARGET',
                'Percentage Target',
                'शेकडेवारी लक्ष्य',
                'Connect fractions, decimals and percentages.',
                'अपूर्णांक, दशांश आणि शेकडेवारी यांचा संबंध जोडा.',
                '🎯',
                ['PERCENTAGE' => 1, 'FRACTIONS' => 0.5, 'DECIMALS' => 0.5],
                [
                    $this->gameQuestion('Convert the fraction to a percentage.', '१/४ चे शेकडेवारीत रूपांतर करा.', '२५%', ['२०%', '२५%', '४०%', '७५%'], ['visual' => '■■□□  =  ?%']),
                    $this->gameQuestion('Find १०% of २५०.', '२५० चे १०% किती?', '२५', ['१०', '२०', '२५', '५०'], ['visual' => '२५० ÷ १०']),
                    $this->gameQuestion('Convert ०.६ to a percentage.', '०.६ चे शेकडेवारीत रूपांतर करा.', '६०%', ['६%', '१६%', '६०%', '६००%'], ['visual' => '०.६ × १००']),
                    $this->gameQuestion('A student answered ४० out of ५० correctly. Find the percentage.', '५० पैकी ४० उत्तरे बरोबर आहेत. शेकडेवारी किती?', '८०%', ['४०%', '५०%', '८०%', '९०%'], ['visual' => '४० / ५० × १००']),
                ],
                ['grade_min' => 6, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'ALGEBRA_BALANCE',
                'Algebra Balance',
                'बीजगणित तराजू',
                'Find unknown values and evaluate simple expressions.',
                'अज्ञात संख्या शोधा आणि सोप्या राशींची किंमत काढा.',
                '⚗️',
                ['ALGEBRA' => 1],
                [
                    $this->gameQuestion('Find x.', 'x + ७ = १५, तर x = ?', '८', ['६', '७', '८', '२२'], ['visual' => 'x + ७  ⚖  १५']),
                    $this->gameQuestion('Find y.', '३y = २१, तर y = ?', '७', ['३', '६', '७', '१८'], ['visual' => '३ × y  ⚖  २१']),
                    $this->gameQuestion('Evaluate २a + १ when a = ४.', 'a = ४ असताना २a + १ ची किंमत काढा.', '९', ['७', '८', '९', '१२'], ['visual' => '२ × ४ + १']),
                    $this->gameQuestion('Which expression means five more than n?', 'n पेक्षा ५ ने मोठी संख्या कोणत्या राशीने दाखवली आहे?', 'n + ५', ['५n', 'n − ५', 'n + ५', '५ − n'], ['visual' => 'n पासून पुढे ५']),
                ],
                ['grade_min' => 6, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'ANGLE_DETECTIVE',
                'Angle Detective',
                'कोन शोधक',
                'Classify angles and use angle relationships.',
                'कोनांचे प्रकार आणि कोनांमधील संबंध ओळखा.',
                '📐',
                ['ANGLES' => 1, 'GEOMETRIC_SHAPES' => 0.5],
                [
                    $this->gameQuestion('Classify a ४५° angle.', '४५° कोनाचा प्रकार ओळखा.', 'लघुकोन', ['लघुकोन', 'काटकोन', 'विशालकोन', 'सरळकोन'], ['visual' => '∠ ४५°']),
                    $this->gameQuestion('Classify a ९०° angle.', '९०° कोनाचा प्रकार ओळखा.', 'काटकोन', ['लघुकोन', 'काटकोन', 'विशालकोन', 'पूर्णकोन'], ['visual' => '∟ ९०°']),
                    $this->gameQuestion('Two angles of a triangle are ५०° and ६०°. Find the third angle.', 'त्रिकोणाचे दोन कोन ५०° आणि ६०° आहेत. तिसरा कोन किती?', '७०°', ['६०°', '७०°', '८०°', '११०°'], ['visual' => '△  कोनांची बेरीज = १८०°']),
                    $this->gameQuestion('Find the supplement of १२०°.', '१२०° चा संपूरक कोन शोधा.', '६०°', ['३०°', '६०°', '१२०°', '२४०°'], ['visual' => '१२०° + ? = १८०°']),
                ],
                ['grade_min' => 4, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'PERIMETER_AREA_BUILDER',
                'Perimeter and Area Builder',
                'परिमिती-क्षेत्रफळ बांधणी',
                'Calculate perimeter and area from dimensions.',
                'मापांचा वापर करून परिमिती आणि क्षेत्रफळ काढा.',
                '📏',
                ['PERIMETER_AND_AREA' => 1, 'MEASUREMENT' => 0.5],
                [
                    $this->gameQuestion('Find the perimeter of a square with side ५ cm.', '५ सेमी बाजूच्या चौरसाची परिमिती काढा.', '२० सेमी', ['१० सेमी', '१५ सेमी', '२० सेमी', '२५ सेमी'], ['visual' => '┌─────┐\n│ ५ सेमी │\n└─────┘']),
                    $this->gameQuestion('Find the area of a rectangle ८ cm by ३ cm.', '८ सेमी × ३ सेमी आयताचे क्षेत्रफळ काढा.', '२४ चौ.सेमी', ['११ चौ.सेमी', '१६ चौ.सेमी', '२२ चौ.सेमी', '२४ चौ.सेमी'], ['visual' => 'लांबी ८ × रुंदी ३']),
                    $this->gameQuestion('A rectangle has perimeter ३० cm and length १० cm. Find its width.', 'आयताची परिमिती ३० सेमी आणि लांबी १० सेमी आहे. रुंदी किती?', '५ सेमी', ['३ सेमी', '५ सेमी', '१० सेमी', '१५ सेमी'], ['visual' => '२ × (लांबी + रुंदी) = ३०']),
                    $this->gameQuestion('How many square tiles cover a ६ by ४ floor?', '६ × ४ आकाराच्या फरशीसाठी किती चौरस टाइल्स लागतील?', '२४', ['१०', '२०', '२४', '४८'], ['visual' => '६ स्तंभ × ४ ओळी']),
                ],
                ['grade_min' => 4, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'DATA_GRAPH_CHALLENGE',
                'Data Graph Challenge',
                'माहिती आलेख आव्हान',
                'Read tables, pictographs and bar graphs.',
                'तक्ते, चित्रालेख आणि स्तंभालेख वाचा.',
                '📊',
                ['DATA_HANDLING' => 1],
                [
                    $this->gameQuestion('Which fruit received the most votes?', 'सर्वाधिक मते कोणत्या फळाला मिळाली?', 'आंबा', ['आंबा', 'केळी', 'सफरचंद', 'द्राक्ष'], ['visual' => 'आंबा      ███████ ७\nकेळी      █████ ५\nसफरचंद   ████ ४\nद्राक्ष    ██████ ६']),
                    $this->gameQuestion('How many more books were read on Friday than Monday?', 'सोमवारपेक्षा शुक्रवारी किती जास्त पुस्तके वाचली?', '५', ['२', '३', '५', '९'], ['visual' => 'सोमवार  : ████ ४\nशुक्रवार : █████████ ९']),
                    $this->gameQuestion('Find the total number of students.', 'विद्यार्थ्यांची एकूण संख्या काढा.', '२४', ['१८', '२०', '२२', '२४'], ['visual' => 'बसने १२ · पायी ८ · सायकलने ४']),
                    $this->gameQuestion('What is the mode?', 'बहुलक शोधा.', '४', ['२', '३', '४', '५'], ['visual' => '२, ४, ३, ४, ५, ४, २']),
                ],
                ['grade_min' => 3, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'CLOCK_CALENDAR_QUEST',
                'Clock and Calendar Quest',
                'घड्याळ-दिनदर्शिका मोहीम',
                'Solve elapsed-time and calendar problems.',
                'कालावधी आणि दिनदर्शिकेची उदाहरणे सोडवा.',
                '🗓️',
                ['TIME' => 1, 'WORD_PROBLEMS' => 0.5],
                [
                    $this->gameQuestion('A class starts at ९:३० and lasts ४५ minutes. When does it end?', 'तास ९:३० ला सुरू होऊन ४५ मिनिटे चालतो. तो कधी संपेल?', '१०:१५', ['९:४५', '१०:००', '१०:१५', '१०:३०'], ['visual' => '९:३० + ४५ मिनिटे']),
                    $this->gameQuestion('How many minutes are in २ hours १५ minutes?', '२ तास १५ मिनिटांत एकूण किती मिनिटे?', '१३५', ['११५', '१२०', '१३५', '२१५'], ['visual' => '२ × ६० + १५']),
                    $this->gameQuestion('If today is Wednesday, what day will it be after १० days?', 'आज बुधवार असेल, तर १० दिवसांनी कोणता वार असेल?', 'शनिवार', ['शुक्रवार', 'शनिवार', 'रविवार', 'सोमवार'], ['visual' => '७ दिवस + ३ दिवस']),
                    $this->gameQuestion('How many days are in April?', 'एप्रिल महिन्यात किती दिवस असतात?', '३०', ['२८', '२९', '३०', '३१'], ['visual' => 'एप्रिल दिनदर्शिका']),
                ],
                ['grade_min' => 2, 'grade_max' => 7],
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
     * @return list<array<string, mixed>>
     */
    private function upperPrimaryMarathiGameDefinitions(): array
    {
        return [
            $this->choiceBankGameDefinition(
                'SYNONYM_PAIRS',
                'Synonym Pairs',
                'समानार्थी जोडी',
                'Match words that have a similar meaning.',
                'समान अर्थ असलेल्या शब्दांची जोडी जुळवा.',
                '🔗',
                ['SYNONYMS' => 1, 'VOCABULARY' => 0.5],
                [
                    $this->gameQuestion('Choose a synonym for sun.', '‘सूर्य’ या शब्दाचा समानार्थी शब्द निवडा.', 'रवी', ['रवी', 'चंद्र', 'तारा', 'मेघ'], ['visual' => 'सूर्य  ↔  ?']),
                    $this->gameQuestion('Choose a synonym for forest.', '‘वन’ या शब्दाचा समानार्थी शब्द निवडा.', 'अरण्य', ['अरण्य', 'आकाश', 'सागर', 'नगर'], ['visual' => 'वन  ↔  ?']),
                    $this->gameQuestion('Choose a synonym for joy.', '‘आनंद’ या शब्दाचा समानार्थी शब्द निवडा.', 'हर्ष', ['हर्ष', 'दुःख', 'राग', 'भीती'], ['visual' => 'आनंद  ↔  ?']),
                    $this->gameQuestion('Choose a synonym for earth.', '‘पृथ्वी’ या शब्दाचा समानार्थी शब्द निवडा.', 'धरा', ['धरा', 'दिशा', 'नदी', 'हवा'], ['visual' => 'पृथ्वी  ↔  ?']),
                ],
                ['grade_min' => 3, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'ANTONYM_PAIRS',
                'Antonym Pairs',
                'विरुद्धार्थी जोडी',
                'Match words with opposite meanings.',
                'विरुद्ध अर्थ असलेल्या शब्दांची जोडी जुळवा.',
                '↔️',
                ['ANTONYMS' => 1, 'VOCABULARY' => 0.5],
                [
                    $this->gameQuestion('Choose the antonym of beginning.', '‘आरंभ’ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'शेवट', ['शेवट', 'सुरुवात', 'प्रारंभ', 'उदय'], ['visual' => 'आरंभ  ↔  ?']),
                    $this->gameQuestion('Choose the antonym of ancient.', '‘प्राचीन’ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'आधुनिक', ['नवीन', 'आधुनिक', 'जुने', 'ऐतिहासिक'], ['visual' => 'प्राचीन  ↔  ?']),
                    $this->gameQuestion('Choose the antonym of victory.', '‘विजय’ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'पराजय', ['यश', 'पराजय', 'अभिमान', 'प्रयत्न'], ['visual' => 'विजय  ↔  ?']),
                    $this->gameQuestion('Choose the antonym of clean.', '‘स्वच्छ’ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'अस्वच्छ', ['सुंदर', 'अस्वच्छ', 'निर्मळ', 'सुगंधी'], ['visual' => 'स्वच्छ  ↔  ?']),
                ],
                ['grade_min' => 3, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'GENDER_NUMBER_SORT',
                'Gender and Number Sort',
                'लिंग-वचन वर्गीकरण',
                'Change and classify Marathi gender and number forms.',
                'मराठी शब्दांचे लिंग आणि वचन ओळखा व बदला.',
                '🗂️',
                ['GENDER_AND_NUMBER' => 1],
                [
                    $this->gameQuestion('Choose the feminine form of tiger.', '‘वाघ’ या शब्दाचे स्त्रीलिंगी रूप निवडा.', 'वाघीण', ['वाघीण', 'वाघिणी', 'वाघाचे', 'वाघांना'], ['visual' => 'वाघ  →  ?']),
                    $this->gameQuestion('Choose the plural form of flower.', '‘फूल’ या शब्दाचे अनेकवचन निवडा.', 'फुले', ['फुल', 'फुले', 'फुली', 'फुलांचा'], ['visual' => 'एक फूल  →  अनेक ?']),
                    $this->gameQuestion('Which word is neuter gender?', 'नपुंसकलिंगी शब्द कोणता?', 'घर', ['मुलगा', 'मुलगी', 'घर', 'राजा'], ['visual' => 'पुल्लिंग · स्त्रीलिंग · नपुंसकलिंग']),
                    $this->gameQuestion('Choose the singular form.', '‘मुले’ या शब्दाचे एकवचन निवडा.', 'मूल', ['मुलगा', 'मूल', 'मुली', 'मुलांना'], ['visual' => 'अनेक मुले  →  एक ?']),
                ],
                ['grade_min' => 3, 'grade_max' => 6],
            ),
            $this->choiceBankGameDefinition(
                'WORD_CLASS_DETECTIVE',
                'Word Class Detective',
                'शब्दजात शोधक',
                'Identify nouns, pronouns, adjectives and verbs in context.',
                'वाक्यातील नाम, सर्वनाम, विशेषण आणि क्रियापद ओळखा.',
                '🕵️',
                ['PARTS_OF_SPEECH' => 1, 'SENTENCE_READING' => 0.5],
                [
                    $this->gameQuestion('Identify the underlined word class.', '‘सीमा सुंदर चित्र काढते.’ या वाक्यात ‘सुंदर’ हा कोणता शब्दप्रकार?', 'विशेषण', ['नाम', 'सर्वनाम', 'विशेषण', 'क्रियापद'], ['context_marathi' => 'सीमा सुंदर चित्र काढते.']),
                    $this->gameQuestion('Identify the verb.', '‘पक्षी आकाशात उडतो.’ या वाक्यातील क्रियापद निवडा.', 'उडतो', ['पक्षी', 'आकाशात', 'उडतो', 'या'], ['context_marathi' => 'पक्षी आकाशात उडतो.']),
                    $this->gameQuestion('Identify the pronoun.', '‘ती रोज अभ्यास करते.’ या वाक्यातील सर्वनाम निवडा.', 'ती', ['ती', 'रोज', 'अभ्यास', 'करते'], ['context_marathi' => 'ती रोज अभ्यास करते.']),
                    $this->gameQuestion('Identify the noun.', '‘मुलगा चेंडू खेळतो.’ या वाक्यात वस्तूचे नाम कोणते?', 'चेंडू', ['मुलगा', 'चेंडू', 'खेळतो', 'तो'], ['context_marathi' => 'मुलगा चेंडू खेळतो.']),
                ],
                ['grade_min' => 4, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'TENSE_TRAVEL',
                'Tense Travel',
                'काळप्रवास',
                'Identify and transform past, present and future tense.',
                'भूतकाळ, वर्तमानकाळ आणि भविष्यकाळ ओळखा व बदला.',
                '⏳',
                ['TENSE' => 1, 'SENTENCE_FORMATION' => 0.5],
                [
                    $this->gameQuestion('Identify the tense.', '‘मी पुस्तक वाचतो.’ या वाक्याचा काळ ओळखा.', 'वर्तमानकाळ', ['भूतकाळ', 'वर्तमानकाळ', 'भविष्यकाळ', 'अपूर्ण काळ'], ['visual' => 'काल  ←  आज  →  उद्या']),
                    $this->gameQuestion('Choose the past-tense sentence.', 'भूतकाळातील वाक्य निवडा.', 'ती शाळेत गेली.', ['ती शाळेत जाते.', 'ती शाळेत गेली.', 'ती शाळेत जाईल.', 'ती शाळेत जात आहे.'], ['visual' => 'काल']),
                    $this->gameQuestion('Change to future tense.', '‘आम्ही सामना खेळतो.’ हे वाक्य भविष्यकाळात बदला.', 'आम्ही सामना खेळू.', ['आम्ही सामना खेळलो.', 'आम्ही सामना खेळतो.', 'आम्ही सामना खेळू.', 'आम्ही सामना खेळत होतो.'], ['visual' => 'आज  →  उद्या']),
                    $this->gameQuestion('Identify the tense.', '‘पाऊस पडला.’ या वाक्याचा काळ ओळखा.', 'भूतकाळ', ['भूतकाळ', 'वर्तमानकाळ', 'भविष्यकाळ', 'आज्ञार्थ'], ['visual' => 'घटना पूर्ण झाली']),
                ],
                ['grade_min' => 4, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'IDIOM_CONTEXT',
                'Idiom Context',
                'वाक्प्रचार संदर्भ',
                'Choose the meaning of idioms and proverbs from context.',
                'संदर्भावरून वाक्प्रचार आणि म्हणींचा अर्थ निवडा.',
                '💬',
                ['IDIOMS_AND_PROVERBS' => 1, 'COMPREHENSION' => 0.5],
                [
                    $this->gameQuestion('Choose the meaning of the idiom.', '‘डोळ्यांत तेल घालून पाहणे’ या वाक्प्रचाराचा अर्थ निवडा.', 'अतिशय काळजीपूर्वक लक्ष ठेवणे', ['झोपणे', 'अतिशय काळजीपूर्वक लक्ष ठेवणे', 'रडणे', 'दिवा लावणे'], ['context_marathi' => 'रक्षकाने डोळ्यांत तेल घालून किल्ल्याची राखण केली.']),
                    $this->gameQuestion('Choose the meaning of the idiom.', '‘हातभार लावणे’ याचा अर्थ काय?', 'मदत करणे', ['काम थांबवणे', 'मदत करणे', 'हात धुणे', 'भांडण करणे'], ['context_marathi' => 'सर्वांनी स्वच्छता मोहिमेला हातभार लावला.']),
                    $this->gameQuestion('Complete the proverb.', 'म्हण पूर्ण करा: थेंबे थेंबे ____ साचे.', 'तळे', ['नदी', 'समुद्र', 'तळे', 'विहीर'], ['visual' => 'लहान प्रयत्नांतून मोठे काम']),
                    $this->gameQuestion('Choose the lesson of the proverb.', '‘जशी करणी तशी भरणी’ या म्हणीचा बोध निवडा.', 'कर्माप्रमाणे फळ मिळते', ['नेहमी धावावे', 'कर्माप्रमाणे फळ मिळते', 'पैसे साठवावेत', 'एकटे राहावे'], ['visual' => 'कृती  →  परिणाम']),
                ],
                ['grade_min' => 5, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'PUNCTUATION_RESCUE',
                'Punctuation Rescue',
                'विरामचिन्ह बचाव',
                'Choose punctuation that makes a sentence clear.',
                'वाक्याचा अर्थ स्पष्ट करणारे योग्य विरामचिन्ह निवडा.',
                '❗',
                ['PUNCTUATION' => 1, 'SENTENCE_READING' => 0.5],
                [
                    $this->gameQuestion('Choose the missing punctuation.', 'अरे वा__ किती सुंदर चित्र आहे!', '!', ['.', ',', '?', '!'], ['visual' => 'अरे वा __']),
                    $this->gameQuestion('Choose the missing punctuation.', 'तुझे नाव काय आहे__', '?', ['.', ',', '?', '!'], ['visual' => 'प्रश्न विचारला आहे']),
                    $this->gameQuestion('Choose the correctly punctuated sentence.', 'योग्य विरामचिन्हे असलेले वाक्य निवडा.', 'आई म्हणाली, “लवकर ये.”', ['आई म्हणाली लवकर ये', 'आई म्हणाली, “लवकर ये.”', 'आई म्हणाली? लवकर ये!', 'आई म्हणाली; लवकर ये?'], ['visual' => 'बोललेले वाक्य']),
                    $this->gameQuestion('Choose the missing punctuation.', 'आंबा__ केळी आणि संत्री ही फळे आहेत.', ',', ['.', ',', '?', ':'], ['visual' => 'यादीतील शब्द वेगळे करा']),
                ],
                ['grade_min' => 3, 'grade_max' => 7],
            ),
            $this->choiceBankGameDefinition(
                'POETRY_EXPLORER',
                'Poetry Explorer',
                'कविता शोधयात्रा',
                'Read short original verse and answer meaning and imagery questions.',
                'लहान मूळ कविता वाचून अर्थ आणि प्रतिमांवरील प्रश्न सोडवा.',
                '🎵',
                ['POETRY_COMPREHENSION' => 1, 'COMPREHENSION' => 0.5],
                [
                    $this->gameQuestion('What wakes with the morning light?', 'सकाळच्या प्रकाशाबरोबर काय जागे होते?', 'फुले', ['तारे', 'फुले', 'रात्र', 'दिवे'], ['context_marathi' => "उजाडता हसते ऊन,\nफुले जागी होती;\nपाखरांच्या गाण्याने,\nनवी सकाळ येती."]),
                    $this->gameQuestion('Which sound welcomes the morning?', 'सकाळचे स्वागत कोणता आवाज करतो?', 'पाखरांचे गाणे', ['गाड्यांचा आवाज', 'पाखरांचे गाणे', 'ढगांचा गडगडाट', 'घड्याळ'], ['context_marathi' => "उजाडता हसते ऊन,\nफुले जागी होती;\nपाखरांच्या गाण्याने,\nनवी सकाळ येती."]),
                    $this->gameQuestion('What does the little stream do?', 'लहान ओढा काय करतो?', 'गात गात पुढे जातो', ['थांबतो', 'गात गात पुढे जातो', 'आकाशात उडतो', 'झाडावर चढतो'], ['context_marathi' => "डोंगरातून झरा येतो,\nगात गात पुढे जातो;\nतहानलेल्या हिरव्या रानाला,\nथंड पाणी देत राहतो."]),
                    $this->gameQuestion('What does the stream give the fields?', 'ओढा रानाला काय देतो?', 'थंड पाणी', ['फुले', 'थंड पाणी', 'वारा', 'सूर्यप्रकाश'], ['context_marathi' => "डोंगरातून झरा येतो,\nगात गात पुढे जातो;\nतहानलेल्या हिरव्या रानाला,\nथंड पाणी देत राहतो."]),
                ],
                ['grade_min' => 4, 'grade_max' => 7],
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
        array $configuration = [],
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
                    ...$configuration,
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
        array $configuration = [],
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
            $configuration,
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

    private function createClass4LearningOutcomes(Subject $marathi, Subject $mathematics): void
    {
        $definitions = [
            [$mathematics, 'PLACE_VALUE', 'STD4-MATH-01', 'Reads, writes and represents numbers using place value.', 'स्थानिक किमतीचा वापर करून संख्या वाचतो, लिहितो व दर्शवतो.', 'Number sense and place value', 'संख्याज्ञान व स्थानिक किंमत'],
            [$mathematics, 'NUMBER_COMPARISON', 'STD4-MATH-02', 'Compares and orders numbers.', 'संख्यांची तुलना करून चढता व उतरता क्रम लावतो.', 'Number comparison', 'संख्या तुलना'],
            [$mathematics, 'ADDITION', 'STD4-MATH-03', 'Adds multi-digit numbers in meaningful situations.', 'दैनंदिन परिस्थितीत अनेक अंकी संख्यांची बेरीज करतो.', 'Addition', 'बेरीज'],
            [$mathematics, 'SUBTRACTION', 'STD4-MATH-04', 'Subtracts multi-digit numbers in meaningful situations.', 'दैनंदिन परिस्थितीत अनेक अंकी संख्यांची वजाबाकी करतो.', 'Subtraction', 'वजाबाकी'],
            [$mathematics, 'MULTIPLICATION', 'STD4-MATH-05', 'Uses multiplication to solve equal-group problems.', 'समान गटांच्या समस्या गुणाकाराने सोडवतो.', 'Multiplication', 'गुणाकार'],
            [$mathematics, 'DIVISION', 'STD4-MATH-06', 'Uses division for equal sharing and grouping.', 'समान वाटणी व गट करण्यासाठी भागाकार वापरतो.', 'Division', 'भागाकार'],
            [$mathematics, 'FRACTIONS', 'STD4-MATH-07', 'Recognizes, compares and represents simple fractions.', 'साधे अपूर्णांक ओळखतो, दर्शवतो व तुलना करतो.', 'Fractions', 'अपूर्णांक'],
            [$mathematics, 'TIME', 'STD4-MATH-08', 'Reads time and solves elapsed-time situations.', 'घड्याळातील वेळ वाचतो व कालावधीच्या समस्या सोडवतो.', 'Time', 'वेळ'],
            [$mathematics, 'MONEY', 'STD4-MATH-09', 'Solves everyday problems involving money.', 'पैशांवरील दैनंदिन व्यवहारांच्या समस्या सोडवतो.', 'Money', 'पैसे'],
            [$mathematics, 'MEASUREMENT', 'STD4-MATH-10', 'Estimates and measures length, mass and capacity.', 'लांबी, वस्तुमान व धारकता यांचा अंदाज व मोजमाप करतो.', 'Measurement', 'मोजमाप'],
            [$mathematics, 'GEOMETRIC_SHAPES', 'STD4-MATH-11', 'Identifies properties of common geometric shapes.', 'सामान्य भूमितीय आकारांचे गुणधर्म ओळखतो.', 'Geometry', 'भूमिती'],
            [$mathematics, 'PATTERNS', 'STD4-MATH-12', 'Identifies and extends number and shape patterns.', 'संख्या व आकारांतील आकृतिबंध ओळखून पुढे नेतो.', 'Patterns', 'आकृतिबंध'],
            [$mathematics, 'WORD_PROBLEMS', 'STD4-MATH-13', 'Chooses operations to solve contextual problems.', 'शाब्दिक समस्येसाठी योग्य गणिती क्रिया निवडून उत्तर काढतो.', 'Problem solving', 'समस्या निराकरण'],
            [$marathi, 'PARAGRAPH_READING', 'STD4-MAR-01', 'Reads an age-appropriate passage fluently.', 'वयाला अनुरूप परिच्छेद योग्य गती व लयीत वाचतो.', 'Fluent reading', 'प्रवाही वाचन'],
            [$marathi, 'COMPREHENSION', 'STD4-MAR-02', 'Finds explicit meaning and draws simple inferences from a passage.', 'उताऱ्यातील स्पष्ट अर्थ समजून साधा निष्कर्ष काढतो.', 'Reading comprehension', 'वाचन आकलन'],
            [$marathi, 'VOCABULARY', 'STD4-MAR-03', 'Understands and uses words in context.', 'संदर्भानुसार शब्दांचा अर्थ समजून योग्य वापर करतो.', 'Vocabulary', 'शब्दसंग्रह'],
            [$marathi, 'SENTENCE_FORMATION', 'STD4-MAR-04', 'Forms meaningful and grammatically appropriate sentences.', 'अर्थपूर्ण व व्याकरणदृष्ट्या योग्य वाक्य तयार करतो.', 'Sentence construction', 'वाक्यरचना'],
            [$marathi, 'GENDER_AND_NUMBER', 'STD4-MAR-05', 'Uses gender and number forms correctly.', 'लिंग व वचनाची योग्य रूपे वापरतो.', 'Gender and number', 'लिंग व वचन'],
            [$marathi, 'PARTS_OF_SPEECH', 'STD4-MAR-06', 'Identifies basic word classes in sentences.', 'वाक्यातील नाम, सर्वनाम, विशेषण व क्रियापद ओळखतो.', 'Word classes', 'शब्दांच्या जाती'],
            [$marathi, 'TENSE', 'STD4-MAR-07', 'Recognizes and uses simple tense forms.', 'काळाची सोपी रूपे ओळखून वापरतो.', 'Tense', 'काळ'],
            [$marathi, 'PUNCTUATION', 'STD4-MAR-08', 'Uses punctuation to make written meaning clear.', 'लेखनाचा अर्थ स्पष्ट होण्यासाठी योग्य विरामचिन्हे वापरतो.', 'Punctuation', 'विरामचिन्हे'],
            [$marathi, 'POETRY_COMPREHENSION', 'STD4-MAR-09', 'Responds to the central idea and imagery in a poem.', 'कवितेतील मध्यवर्ती कल्पना व प्रतिमांना प्रतिसाद देतो.', 'Poetry comprehension', 'कविता आकलन'],
        ];

        foreach ($definitions as $index => [$subject, $skillCode, $code, $statement, $statementMarathi, $competency, $competencyMarathi]) {
            LearningOutcome::query()->create([
                'subject_id' => $subject->id,
                'skill_id' => $subject->skills()->where('code', $skillCode)->valueOrFail('skills.id'),
                'grade_level' => 4,
                'code' => $code,
                'statement' => $statement,
                'statement_marathi' => $statementMarathi,
                'competency' => $competency,
                'competency_marathi' => $competencyMarathi,
                'sort_order' => $index + 1,
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
            ['MARATHI', 'MARATHI_CLASS4_PRE', 'pre_test', 'Class 4 Marathi baseline', 'इयत्ता चौथी मराठी पूर्व चाचणी'],
            ['MARATHI', 'MARATHI_CLASS4_POST', 'post_test', 'Class 4 Marathi reassessment', 'इयत्ता चौथी मराठी उत्तर चाचणी'],
            ['MATHEMATICS', 'MATH_CLASS4_PRE', 'pre_test', 'Class 4 Mathematics baseline', 'इयत्ता चौथी गणित पूर्व चाचणी'],
            ['MATHEMATICS', 'MATH_CLASS4_POST', 'post_test', 'Class 4 Mathematics reassessment', 'इयत्ता चौथी गणित उत्तर चाचणी'],
        ];
        $assessedOutcomes = LearningOutcome::query()
            ->whereIn('code', [
                'STD4-MATH-01', 'STD4-MATH-02', 'STD4-MATH-03', 'STD4-MATH-04',
                'STD4-MATH-05', 'STD4-MATH-06', 'STD4-MATH-07', 'STD4-MATH-13',
                'STD4-MAR-01', 'STD4-MAR-02', 'STD4-MAR-03', 'STD4-MAR-04',
                'STD4-MAR-05', 'STD4-MAR-08',
            ])
            ->with(['skill', 'subject'])
            ->orderBy('subject_id')
            ->orderBy('sort_order')
            ->get();
        $questionsBySubjectAndType = [];

        foreach ($assessedOutcomes as $outcome) {
            $errorType = $outcome->skill->errorTypes()->firstOrCreate(
                ['code' => "CLASS4_{$outcome->skill->code}_ERROR"],
                [
                    'name' => "{$outcome->skill->name} misconception",
                    'name_marathi' => "{$outcome->skill->name_marathi} संकल्पनेतील चूक",
                    'description' => 'The response indicates that this Class 4 competency needs targeted support.',
                    'remediation' => ['hint' => 'Use a worked example, guided practice and a matching learning game.'],
                ],
            );
            foreach ($this->class4AssessmentQuestions($outcome->code) as $index => $definition) {
                $question = Question::query()->create([
                    'skill_id' => $outcome->skill_id,
                    'learning_outcome_id' => $outcome->id,
                    'error_type_id' => $errorType->id,
                    'created_by' => $creator->id,
                    'type' => 'mcq',
                    'prompt' => $definition['prompt'],
                    'prompt_marathi' => $definition['prompt_marathi'],
                    'correct_answer' => [],
                    'explanation' => $definition['explanation'],
                    'explanation_marathi' => $definition['explanation_marathi'],
                    'difficulty' => 4,
                    'marks' => 1,
                    'is_active' => true,
                ]);
                $question->options()->createMany(collect($definition['choices'])
                    ->map(fn (string $choice, int $choiceIndex): array => [
                        'label' => $choice,
                        'label_marathi' => $choice,
                        'is_correct' => $choice === $definition['answer'],
                        'sort_order' => $choiceIndex + 1,
                    ])->all());
                $type = $index < 3 ? 'pre_test' : 'post_test';
                $questionsBySubjectAndType[$outcome->subject->code][$type][] = $question;
            }
        }

        foreach ($assessmentDefinitions as [$subjectCode, $code, $type, $title, $titleMarathi]) {
            $subject = Subject::query()->where('code', $subjectCode)->firstOrFail();
            $questions = collect($questionsBySubjectAndType[$subjectCode][$type] ?? []);
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
                'duration_minutes' => $subjectCode === 'MATHEMATICS' ? 40 : 30,
                'difficulty' => 4,
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

    /**
     * @return list<array{prompt: string, prompt_marathi: string, answer: string, choices: list<string>, explanation: string, explanation_marathi: string}>
     */
    private function class4AssessmentQuestions(string $outcomeCode): array
    {
        return match ($outcomeCode) {
            'STD4-MATH-01' => [
                $this->assessmentChoice('What is the place value of 7 in 3,742?', '३,७४२ मध्ये ७ ची स्थानिक किंमत किती?', '700', ['70', '700', '7', '7000']),
                $this->assessmentChoice('Which is the expanded form of 5,206?', '५,२०६ चे विस्तारित रूप कोणते?', '5000 + 200 + 6', ['500 + 20 + 6', '5000 + 20 + 6', '5000 + 200 + 6', '520 + 6']),
                $this->assessmentChoice('Which digit is in the hundreds place in 8,451?', '८,४५१ मध्ये शतक स्थानावरील अंक कोणता?', '4', ['8', '5', '4', '1']),
                $this->assessmentChoice('What is the place value of 6 in 6,318?', '६,३१८ मध्ये ६ ची स्थानिक किंमत किती?', '6000', ['6000', '600', '60', '6']),
                $this->assessmentChoice('Which number is 4000 + 300 + 20 + 9?', '४००० + ३०० + २० + ९ ही कोणती संख्या?', '4329', ['4239', '4329', '4309', '4029']),
                $this->assessmentChoice('Which digit is in the tens place in 9,276?', '९,२७६ मध्ये दशक स्थानावरील अंक कोणता?', '7', ['9', '2', '7', '6']),
            ],
            'STD4-MATH-02' => [
                $this->assessmentChoice('Choose the greatest number.', 'सर्वात मोठी संख्या निवडा.', '4821', ['4281', '4812', '4821', '4218']),
                $this->assessmentChoice('Which sign makes 3,509 __ 3,590 true?', '३,५०९ __ ३,५९० हे योग्य करण्यासाठी कोणते चिन्ह येईल?', '<', ['>', '<', '=', '+']),
                $this->assessmentChoice('Which number comes first in ascending order?', 'चढत्या क्रमात सर्वप्रथम कोणती संख्या येईल?', '2156', ['2615', '2516', '2156', '2651']),
                $this->assessmentChoice('Choose the smallest number.', 'सर्वात लहान संख्या निवडा.', '6079', ['6709', '6079', '6097', '6790']),
                $this->assessmentChoice('Which sign makes 7,400 __ 7,040 true?', '७,४०० __ ७,०४० हे योग्य करण्यासाठी कोणते चिन्ह येईल?', '>', ['<', '>', '=', '−']),
                $this->assessmentChoice('Which is the correct descending order?', 'योग्य उतरता क्रम कोणता?', '920, 902, 290', ['290, 902, 920', '920, 902, 290', '902, 920, 290', '920, 290, 902']),
            ],
            'STD4-MATH-03' => [
                $this->assessmentChoice('2,348 + 1,275 = ?', '२,३४८ + १,२७५ = ?', '3623', ['3513', '3623', '3523', '3723']),
                $this->assessmentChoice('4,509 + 786 = ?', '४,५०९ + ७८६ = ?', '5295', ['5285', '5295', '5195', '5395']),
                $this->assessmentChoice('A library has 1,245 Marathi and 986 English books. How many altogether?', 'ग्रंथालयात १,२४५ मराठी व ९८६ इंग्रजी पुस्तके आहेत. एकूण किती?', '2231', ['2131', '2231', '2241', '2331']),
                $this->assessmentChoice('3,675 + 2,148 = ?', '३,६७५ + २,१४८ = ?', '5823', ['5723', '5813', '5823', '5923']),
                $this->assessmentChoice('6,090 + 875 = ?', '६,०९० + ८७५ = ?', '6965', ['6855', '6965', '6975', '7065']),
                $this->assessmentChoice('There are 2,360 boys and 2,195 girls. How many children?', '२,३६० मुले व २,१९५ मुली आहेत. एकूण मुले किती?', '4555', ['4455', '4555', '4565', '4655']),
            ],
            'STD4-MATH-04' => [
                $this->assessmentChoice('5,642 − 2,318 = ?', '५,६४२ − २,३१८ = ?', '3324', ['3224', '3324', '3424', '3314']),
                $this->assessmentChoice('7,000 − 2,675 = ?', '७,००० − २,६७५ = ?', '4325', ['4225', '4325', '4425', '4335']),
                $this->assessmentChoice('A shop had 3,250 pencils and sold 1,475. How many remain?', 'दुकानात ३,२५० पेन्सिली होत्या. १,४७५ विकल्या. किती उरल्या?', '1775', ['1675', '1775', '1875', '1785']),
                $this->assessmentChoice('8,431 − 3,209 = ?', '८,४३१ − ३,२०९ = ?', '5222', ['5122', '5222', '5322', '5232']),
                $this->assessmentChoice('6,005 − 879 = ?', '६,००५ − ८७९ = ?', '5126', ['5026', '5116', '5126', '5226']),
                $this->assessmentChoice('There were 4,800 litres; 2,365 litres were used. How many remain?', '४,८०० लिटरपैकी २,३६५ लिटर वापरले. किती उरले?', '2435', ['2335', '2435', '2445', '2535']),
            ],
            'STD4-MATH-05' => [
                $this->assessmentChoice('24 × 6 = ?', '२४ × ६ = ?', '144', ['124', '134', '144', '154']),
                $this->assessmentChoice('38 × 4 = ?', '३८ × ४ = ?', '152', ['142', '152', '162', '172']),
                $this->assessmentChoice('There are 7 rows of 16 plants. How many plants?', '१६ रोपांच्या ७ रांगा आहेत. एकूण रोपे किती?', '112', ['102', '112', '122', '132']),
                $this->assessmentChoice('32 × 8 = ?', '३२ × ८ = ?', '256', ['246', '256', '266', '276']),
                $this->assessmentChoice('45 × 5 = ?', '४५ × ५ = ?', '225', ['215', '225', '235', '245']),
                $this->assessmentChoice('Nine boxes hold 23 books each. How many books?', '९ पेट्यांत प्रत्येकी २३ पुस्तके आहेत. एकूण किती?', '207', ['197', '207', '217', '227']),
            ],
            'STD4-MATH-06' => [
                $this->assessmentChoice('96 ÷ 8 = ?', '९६ ÷ ८ = ?', '12', ['10', '11', '12', '13']),
                $this->assessmentChoice('144 ÷ 12 = ?', '१४४ ÷ १२ = ?', '12', ['11', '12', '13', '14']),
                $this->assessmentChoice('84 sweets are shared among 7 children. How many each?', '८४ गोळ्या ७ मुलांत समान वाटल्या. प्रत्येकाला किती?', '12', ['10', '11', '12', '14']),
                $this->assessmentChoice('156 ÷ 12 = ?', '१५६ ÷ १२ = ?', '13', ['11', '12', '13', '14']),
                $this->assessmentChoice('168 ÷ 8 = ?', '१६८ ÷ ८ = ?', '21', ['19', '20', '21', '22']),
                $this->assessmentChoice('132 flowers make 11 equal garlands. Flowers per garland?', '१३२ फुलांच्या ११ समान माळा केल्या. प्रत्येक माळेत किती फुले?', '12', ['10', '11', '12', '13']),
            ],
            'STD4-MATH-07' => [
                $this->assessmentChoice('Which fraction means one part out of four equal parts?', 'चार समान भागांपैकी एक भाग कोणता अपूर्णांक दाखवतो?', '1/4', ['1/2', '1/3', '1/4', '4/1']),
                $this->assessmentChoice('Which fraction is equal to 1/2?', '१/२ च्या बरोबरीचा अपूर्णांक कोणता?', '2/4', ['1/4', '2/3', '2/4', '3/4']),
                $this->assessmentChoice('Which is greater?', 'मोठा अपूर्णांक कोणता?', '3/4', ['1/4', '2/4', '3/4', '1/2']),
                $this->assessmentChoice('Which fraction means three parts out of eight?', 'आठ समान भागांपैकी तीन भाग कोणता अपूर्णांक दाखवतो?', '3/8', ['8/3', '3/8', '3/5', '1/8']),
                $this->assessmentChoice('Which fraction is equal to 2/3?', '२/३ च्या बरोबरीचा अपूर्णांक कोणता?', '4/6', ['3/6', '4/6', '2/6', '5/6']),
                $this->assessmentChoice('Which is smaller?', 'लहान अपूर्णांक कोणता?', '1/5', ['1/2', '1/3', '1/4', '1/5']),
            ],
            'STD4-MATH-13' => [
                $this->assessmentChoice('A bus carries 48 children. How many children in 5 buses?', 'एका बसमध्ये ४८ मुले आहेत. ५ बसमध्ये किती मुले?', '240', ['230', '240', '250', '260']),
                $this->assessmentChoice('₹500 is shared equally among 5 children. How much each?', '₹५०० पाच मुलांत समान वाटले. प्रत्येकाला किती?', '₹100', ['₹50', '₹100', '₹150', '₹250']),
                $this->assessmentChoice('Meena had 325 beads and bought 178 more. How many now?', 'मीनाकडे ३२५ मणी होते. तिने आणखी १७८ घेतले. आता किती?', '503', ['493', '503', '513', '523']),
                $this->assessmentChoice('36 notebooks are packed in each box. How many in 7 boxes?', 'प्रत्येक पेटीत ३६ वह्या आहेत. ७ पेट्यांत किती?', '252', ['242', '252', '262', '272']),
                $this->assessmentChoice('A 960 m rope is cut into 8 equal parts. Length of each?', '९६० मीटर दोरीचे ८ समान भाग केले. प्रत्येक भाग किती?', '120 m', ['110 m', '120 m', '130 m', '140 m']),
                $this->assessmentChoice('A school collected ₹2,450 and spent ₹1,275. What remains?', 'शाळेने ₹२,४५० जमा केले व ₹१,२७५ खर्च केले. किती उरले?', '₹1175', ['₹1075', '₹1175', '₹1275', '₹1375']),
            ],
            'STD4-MAR-01' => [
                $this->assessmentChoice('Read and choose: The sparrow built a nest on the tree.', 'वाचा: चिमणीने झाडावर घरटे बांधले. चिमणीने घरटे कुठे बांधले?', 'झाडावर', ['घरात', 'झाडावर', 'शाळेत', 'नदीवर']),
                $this->assessmentChoice('Read and choose: Ravi waters the plants every morning.', 'वाचा: रवी रोज सकाळी झाडांना पाणी घालतो. रवी पाणी कधी घालतो?', 'सकाळी', ['दुपारी', 'रात्री', 'सकाळी', 'संध्याकाळी']),
                $this->assessmentChoice('Read and choose: The children happily played in the ground.', 'वाचा: मुले मैदानात आनंदाने खेळली. मुले कुठे खेळली?', 'मैदानात', ['वर्गात', 'घरात', 'मैदानात', 'बागेत']),
                $this->assessmentChoice('Read and choose: Grandmother told an interesting story.', 'वाचा: आजीने एक सुंदर गोष्ट सांगितली. गोष्ट कोणी सांगितली?', 'आजीने', ['आईने', 'आजीने', 'मुलाने', 'शिक्षकांनी']),
                $this->assessmentChoice('Read and choose: The farmer went to the field before sunrise.', 'वाचा: सूर्योदयापूर्वी शेतकरी शेतात गेला. शेतकरी कधी गेला?', 'सूर्योदयापूर्वी', ['दुपारी', 'सूर्योदयापूर्वी', 'रात्री', 'संध्याकाळी']),
                $this->assessmentChoice('Read and choose: The peacock spread its colourful feathers.', 'वाचा: मोराने रंगीबेरंगी पिसारा फुलवला. पिसारा कोणी फुलवला?', 'मोराने', ['पोपटाने', 'चिमणीने', 'मोराने', 'कावळ्याने']),
            ],
            'STD4-MAR-02' => [
                $this->assessmentChoice('Sita carried an umbrella because dark clouds gathered. Why?', 'काळे ढग जमल्यामुळे सीताने छत्री घेतली. तिने छत्री का घेतली?', 'पाऊस येण्याची शक्यता होती', ['ऊन होते', 'पाऊस येण्याची शक्यता होती', 'थंडी होती', 'वारा नव्हता']),
                $this->assessmentChoice('Amit returned the lost wallet to its owner. What quality does this show?', 'अमितने सापडलेले पाकीट मालकाला परत केले. त्याचा कोणता गुण दिसतो?', 'प्रामाणिकपणा', ['आळस', 'राग', 'प्रामाणिकपणा', 'भीती']),
                $this->assessmentChoice('Plants drooped because they had not been watered. What do they need?', 'पाणी न दिल्याने रोपे कोमेजली. त्यांना कशाची गरज आहे?', 'पाण्याची', ['रंगाची', 'पाण्याची', 'खेळण्याची', 'पुस्तकाची']),
                $this->assessmentChoice('Neha finished her work before playing. What did she do first?', 'नेहाने खेळण्यापूर्वी गृहपाठ पूर्ण केला. तिने आधी काय केले?', 'गृहपाठ', ['खेळ', 'जेवण', 'गृहपाठ', 'झोप']),
                $this->assessmentChoice('The road was wet although the rain had stopped. What likely happened?', 'पाऊस थांबला होता, तरी रस्ता ओला होता. यापूर्वी काय झाले असावे?', 'पाऊस पडला होता', ['ऊन पडले', 'पाऊस पडला होता', 'बर्फ पडला', 'वादळ नव्हते']),
                $this->assessmentChoice('The puppy wagged its tail on seeing Raju. How did it feel?', 'राजूला पाहून पिल्लाने शेपटी हलवली. त्याला कसे वाटले?', 'आनंद झाला', ['राग आला', 'आनंद झाला', 'भीती वाटली', 'झोप आली']),
            ],
            'STD4-MAR-03' => [
                $this->assessmentChoice('Choose the synonym of आनंद.', 'आनंद या शब्दाचा समानार्थी शब्द निवडा.', 'हर्ष', ['दुःख', 'हर्ष', 'राग', 'भीती']),
                $this->assessmentChoice('Choose the opposite of स्वच्छ.', 'स्वच्छ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'अस्वच्छ', ['सुंदर', 'निर्मळ', 'अस्वच्छ', 'मऊ']),
                $this->assessmentChoice('In “विशाल मैदान”, what does विशाल mean?', '“विशाल मैदान” यात विशाल शब्दाचा अर्थ कोणता?', 'खूप मोठे', ['खूप लहान', 'खूप मोठे', 'अंधारलेले', 'रिकामे']),
                $this->assessmentChoice('Choose the synonym of पृथ्वी.', 'पृथ्वी या शब्दाचा समानार्थी शब्द निवडा.', 'धरती', ['आकाश', 'धरती', 'समुद्र', 'वारा']),
                $this->assessmentChoice('Choose the opposite of आरंभ.', 'आरंभ या शब्दाचा विरुद्धार्थी शब्द निवडा.', 'शेवट', ['सुरुवात', 'वेग', 'शेवट', 'मध्य']),
                $this->assessmentChoice('In “मंद वारा”, what does मंद mean?', '“मंद वारा” यात मंद शब्दाचा अर्थ कोणता?', 'हळू', ['वेगवान', 'हळू', 'गरम', 'थंड']),
            ],
            'STD4-MAR-04' => [
                $this->assessmentChoice('Choose the meaningful sentence.', 'अर्थपूर्ण वाक्य निवडा.', 'मी रोज शाळेत जातो.', ['रोज मी जातो शाळेत.', 'मी रोज शाळेत जातो.', 'शाळेत रोज जातो मीला.', 'जातो शाळा रोज मी.']),
                $this->assessmentChoice('Arrange: garden / flowers / bloom / in.', 'योग्य वाक्य निवडा: बागेत / फुले / उमलली / सुंदर.', 'बागेत सुंदर फुले उमलली.', ['सुंदर बागेत उमलली फुले.', 'बागेत सुंदर फुले उमलली.', 'फुले बागेत सुंदरला.', 'उमलली बाग फुले सुंदर.']),
                $this->assessmentChoice('Complete: पक्षी आकाशात ____.', 'वाक्य पूर्ण करा: पक्षी आकाशात ____.', 'उडतात', ['चालतात', 'उडतात', 'पोहतात', 'वाचतात']),
                $this->assessmentChoice('Choose the correct sentence.', 'योग्य वाक्य निवडा.', 'आईने स्वादिष्ट जेवण बनवले.', ['आई स्वादिष्ट बनवले जेवण.', 'आईने स्वादिष्ट जेवण बनवले.', 'जेवण आईने बनवली स्वादिष्ट.', 'स्वादिष्ट आई जेवण बनवलेने.']),
                $this->assessmentChoice('Arrange: river / water / flows / in.', 'योग्य वाक्य निवडा: नदीत / पाणी / वाहते.', 'नदीत पाणी वाहते.', ['पाणी नदीत वाहतात.', 'नदीत पाणी वाहते.', 'वाहते नदी पाणीला.', 'नदीत वाहतो पाणी.']),
                $this->assessmentChoice('Complete: शिक्षक आम्हाला ____.', 'वाक्य पूर्ण करा: शिक्षक आम्हाला ____.', 'शिकवतात', ['खेळतो', 'शिकवतात', 'झोपते', 'उडतात']),
            ],
            'STD4-MAR-05' => [
                $this->assessmentChoice('What is the feminine form of मुलगा?', 'मुलगा या शब्दाचे स्त्रीलिंगी रूप कोणते?', 'मुलगी', ['मुलगे', 'मुलगी', 'मुलांना', 'मुलाचे']),
                $this->assessmentChoice('What is the plural of फूल?', 'फूल या शब्दाचे अनेकवचन कोणते?', 'फुले', ['फुली', 'फुला', 'फुले', 'फुलाचे']),
                $this->assessmentChoice('Choose the plural sentence.', 'अनेकवचनी वाक्य निवडा.', 'मुले खेळत आहेत.', ['मुलगा खेळत आहे.', 'मुलगी खेळत आहे.', 'मुले खेळत आहेत.', 'मुलाला खेळतो.']),
                $this->assessmentChoice('What is the feminine form of राजा?', 'राजा या शब्दाचे स्त्रीलिंगी रूप कोणते?', 'राणी', ['राजे', 'राणी', 'राजाला', 'राजाची']),
                $this->assessmentChoice('What is the plural of पुस्तक?', 'पुस्तक या शब्दाचे अनेकवचन कोणते?', 'पुस्तके', ['पुस्तकी', 'पुस्तकां', 'पुस्तके', 'पुस्तकाचा']),
                $this->assessmentChoice('Choose the singular sentence.', 'एकवचनी वाक्य निवडा.', 'गाय चरते.', ['गायी चरतात.', 'गाय चरते.', 'गायांना चरतात.', 'गायीचे चरतो.']),
            ],
            'STD4-MAR-08' => [
                $this->assessmentChoice('Choose the correctly punctuated question.', 'योग्य विरामचिन्ह असलेले प्रश्नार्थक वाक्य निवडा.', 'तुझे नाव काय आहे?', ['तुझे नाव काय आहे.', 'तुझे नाव काय आहे?', 'तुझे नाव काय आहे!', 'तुझे नाव, काय आहे.']),
                $this->assessmentChoice('Which mark completes: अरे वा__ किती सुंदर चित्र!', 'वाक्य पूर्ण करा: अरे वा__ किती सुंदर चित्र!', '!', ['.', ',', '?', '!']),
                $this->assessmentChoice('Choose the correct comma use.', 'स्वल्पविरामाचा योग्य वापर असलेले वाक्य निवडा.', 'आंबा, सफरचंद आणि केळी आण.', ['आंबा सफरचंद आणि केळी आण?', 'आंबा, सफरचंद आणि केळी आण.', 'आंबा. सफरचंद आणि केळी आण.', 'आंबा! सफरचंद आणि केळी आण.']),
                $this->assessmentChoice('Choose the correctly punctuated statement.', 'योग्य विरामचिन्ह असलेले विधान निवडा.', 'आज सोमवार आहे.', ['आज सोमवार आहे?', 'आज सोमवार आहे.', 'आज सोमवार आहे!', 'आज, सोमवार आहे?']),
                $this->assessmentChoice('Which mark completes: तू उद्या येशील__', 'वाक्य पूर्ण करा: तू उद्या येशील__', '?', ['.', ',', '?', '!']),
                $this->assessmentChoice('Choose the correctly punctuated exclamation.', 'योग्य उद्गारवाचक वाक्य निवडा.', 'शाब्बास! तू जिंकलास.', ['शाब्बास? तू जिंकलास.', 'शाब्बास! तू जिंकलास.', 'शाब्बास, तू जिंकलास?', 'शाब्बास. तू जिंकलास?']),
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>  $choices
     * @return array{prompt: string, prompt_marathi: string, answer: string, choices: list<string>, explanation: string, explanation_marathi: string}
     */
    private function assessmentChoice(
        string $prompt,
        string $promptMarathi,
        string $answer,
        array $choices,
    ): array {
        return [
            'prompt' => $prompt,
            'prompt_marathi' => $promptMarathi,
            'answer' => $answer,
            'choices' => $choices,
            'explanation' => "The correct answer is {$answer}.",
            'explanation_marathi' => "योग्य उत्तर {$answer} आहे.",
        ];
    }
}
