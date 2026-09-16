<?php

namespace App\Console\Commands;

use App\Models\Mentor;
use App\Models\Student;
use App\Services\PortalNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notifications:sync')]
#[Description('Create deduplicated student and mentor portal notifications')]
class SyncPortalNotifications extends Command
{
    public function handle(PortalNotificationService $notifications): int
    {
        $studentCount = 0;
        Student::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('enrollments', fn ($query) => $query->where('status', 'active'))
            ->with([
                'school',
                'user',
                'enrollments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with('academicYear')
                    ->latest('enrolled_on'),
            ])
            ->chunkById(100, function ($students) use ($notifications, &$studentCount): void {
                foreach ($students as $student) {
                    $academicYear = $student->enrollments->firstOrFail()->academicYear;

                    $notifications->syncForStudent($student, $academicYear);
                    $studentCount++;
                }
            });

        $mentorCount = 0;
        Mentor::query()
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with(['school', 'user'])
            ->chunkById(100, function ($mentors) use ($notifications, &$mentorCount): void {
                foreach ($mentors as $mentor) {
                    $notifications->syncForMentor($mentor);
                    $mentorCount++;
                }
            });

        $this->info("Notifications synchronized for {$studentCount} students and {$mentorCount} mentors.");

        return self::SUCCESS;
    }
}
