<?php

namespace Tests\Feature\Portfolio;

use App\Enums\RoleCode;
use App\Models\AcademicYear;
use App\Models\Mentor;
use App\Models\PortfolioItem;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_mentor_uploads_private_portfolio_evidence_and_audit_log(): void
    {
        Storage::fake('local');
        config(['filesystems.portfolio_disk' => 'local']);
        [$user, , $student, $academicYear] = $this->mentorContext();

        $this->actingAs($user)
            ->post(route('mentor.students.portfolio.store', $student), [
                'type' => 'student_work',
                'title' => 'माझे गणित काम',
                'description' => 'स्वतंत्रपणे सोडवलेली उदाहरणे.',
                'occurred_on' => '2026-09-08',
                'file' => UploadedFile::fake()->create('work.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('mentor.students.portfolio.index', $student));

        $item = PortfolioItem::query()->sole();

        $this->assertSame($student->id, $item->student_id);
        $this->assertSame($academicYear->id, $item->academic_year_id);
        $this->assertSame('local', $item->disk);
        $this->assertTrue($item->is_private);
        Storage::disk('local')->assertExists($item->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'portfolio.uploaded',
            'auditable_id' => $item->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_student_can_browse_and_download_only_their_own_portfolio(): void
    {
        Storage::fake('local');
        $school = School::factory()->create();
        $studentRole = $this->role(RoleCode::Student);
        $user = User::factory()->for($school)->create(['role_id' => $studentRole->id]);
        $student = Student::factory()->for($user)->for($school)->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $item = PortfolioItem::factory()
            ->for($student)
            ->for($academicYear)
            ->create([
                'uploaded_by' => $user->id,
                'path' => 'portfolios/private-evidence.pdf',
                'title' => 'Progress Evidence',
            ]);
        Storage::disk('local')->put($item->path, 'private file');

        $this->actingAs($user)
            ->get(route('portfolio.mine'))
            ->assertOk()
            ->assertSee('Progress Evidence');
        $this->actingAs($user)
            ->get(route('portfolio-items.download', $item))
            ->assertOk()
            ->assertDownload('progress-evidence.pdf');
    }

    public function test_cross_school_user_receives_not_found_for_private_file(): void
    {
        $school = School::factory()->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        $item = PortfolioItem::factory()->for($student)->for($academicYear)->create();
        $otherSchool = School::factory()->create();
        $otherUser = User::factory()->for($otherSchool)->create([
            'role_id' => $this->role(RoleCode::SchoolAdmin)->id,
        ]);

        $this->actingAs($otherUser)
            ->get(route('portfolio-items.download', $item))
            ->assertNotFound();
    }

    public function test_uploading_mentor_deletes_database_item_private_file_and_records_audit(): void
    {
        Storage::fake('local');
        [$user, , $student, $academicYear] = $this->mentorContext();
        $item = PortfolioItem::factory()
            ->for($student)
            ->for($academicYear)
            ->create([
                'uploaded_by' => $user->id,
                'path' => 'portfolios/delete-me.pdf',
            ]);
        Storage::disk('local')->put($item->path, 'delete me');

        $this->actingAs($user)
            ->delete(route('mentor.portfolio-items.destroy', $item))
            ->assertRedirect(route('mentor.students.portfolio.index', $student));

        $this->assertSoftDeleted($item);
        Storage::disk('local')->assertMissing($item->path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'portfolio.deleted',
            'auditable_id' => $item->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array{User, Mentor, Student, AcademicYear}
     */
    private function mentorContext(): array
    {
        $school = School::factory()->create(['timezone' => 'Asia/Kolkata']);
        $user = User::factory()->for($school)->create([
            'role_id' => $this->role(RoleCode::Mentor)->id,
        ]);
        $mentor = Mentor::factory()->for($user)->for($school)->create();
        $student = Student::factory()
            ->for(User::factory()->for($school))
            ->for($school)
            ->create();
        $academicYear = AcademicYear::factory()->for($school)->create();
        StudentMentorAssignment::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'academic_year_id' => $academicYear->id,
            'assigned_on' => now('Asia/Kolkata')->subDay()->toDateString(),
            'is_primary' => true,
        ]);

        return [$user, $mentor, $student, $academicYear];
    }

    private function role(RoleCode $role): Role
    {
        return Role::query()->firstOrCreate(
            ['code' => $role->value],
            ['name' => $role->name],
        );
    }
}
