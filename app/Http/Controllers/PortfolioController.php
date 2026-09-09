<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Http\Requests\Mentor\StorePortfolioItemRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Mentor;
use App\Models\PortfolioItem;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentMentorAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PortfolioController extends Controller
{
    public function index(Request $request, Student $student): View
    {
        Gate::authorize('view', $student);

        return $this->portfolioView($request, $student);
    }

    public function mine(Request $request): View
    {
        $student = $request->user()->student()->firstOrFail();
        Gate::authorize('view', $student);

        return $this->portfolioView($request, $student);
    }

    public function store(
        StorePortfolioItemRequest $request,
        Student $student,
    ): RedirectResponse {
        /** @var Mentor $mentor */
        $mentor = $request->user()->mentor;
        $assignment = $this->activeAssignment($request, $student, $mentor);
        $data = $request->validated();
        $disk = (string) config('filesystems.portfolio_disk');
        $file = $request->file('file');
        $path = $file->store(
            "portfolios/{$student->school_id}/{$student->id}/{$assignment->academic_year_id}",
            $disk,
        );

        try {
            DB::transaction(function () use (
                $request,
                $student,
                $assignment,
                $data,
                $disk,
                $file,
                $path,
            ): void {
                $item = PortfolioItem::query()->create([
                    'student_id' => $student->id,
                    'uploaded_by' => $request->user()->id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'skill_id' => $data['skill_id'] ?? null,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'disk' => $disk,
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'is_private' => true,
                    'occurred_on' => $data['occurred_on'] ?? null,
                ]);

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'school_id' => $student->school_id,
                    'action' => 'portfolio.uploaded',
                    'auditable_type' => $item->getMorphClass(),
                    'auditable_id' => $item->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                    'new_values' => [
                        'student_id' => $student->id,
                        'type' => $item->type,
                        'title' => $item->title,
                        'mime_type' => $item->mime_type,
                        'size_bytes' => $item->size_bytes,
                    ],
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        return redirect()
            ->route('mentor.students.portfolio.index', $student)
            ->with('status', 'पोर्टफोलिओ पुरावा सुरक्षितपणे अपलोड केला.');
    }

    public function show(Request $request, PortfolioItem $portfolioItem): StreamedResponse
    {
        Gate::authorize('view', $portfolioItem);
        $extension = pathinfo($portfolioItem->path, PATHINFO_EXTENSION);
        $fileName = $this->downloadName($portfolioItem, $extension);

        if (
            $portfolioItem->mime_type === 'application/pdf'
            || str_starts_with((string) $portfolioItem->mime_type, 'image/')
        ) {
            return Storage::disk($portfolioItem->disk)->response(
                $portfolioItem->path,
                $fileName,
                [
                    'Content-Type' => $portfolioItem->mime_type,
                    'Content-Disposition' => 'inline; filename="'.$fileName.'"',
                    'X-Content-Type-Options' => 'nosniff',
                ],
            );
        }

        return $this->download($request, $portfolioItem);
    }

    public function download(Request $request, PortfolioItem $portfolioItem): StreamedResponse
    {
        Gate::authorize('view', $portfolioItem);
        $extension = pathinfo($portfolioItem->path, PATHINFO_EXTENSION);

        return Storage::disk($portfolioItem->disk)->download(
            $portfolioItem->path,
            $this->downloadName($portfolioItem, $extension),
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function destroy(Request $request, PortfolioItem $portfolioItem): RedirectResponse
    {
        Gate::authorize('delete', $portfolioItem);
        $student = $portfolioItem->student;
        $disk = $portfolioItem->disk;
        $path = $portfolioItem->path;

        DB::transaction(function () use ($request, $portfolioItem, $student, $disk, $path): void {
            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'school_id' => $student->school_id,
                'action' => 'portfolio.deleted',
                'auditable_type' => $portfolioItem->getMorphClass(),
                'auditable_id' => $portfolioItem->id,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
                'old_values' => [
                    'student_id' => $student->id,
                    'type' => $portfolioItem->type,
                    'title' => $portfolioItem->title,
                    'mime_type' => $portfolioItem->mime_type,
                    'size_bytes' => $portfolioItem->size_bytes,
                ],
                'created_at' => now(),
            ]);
            $portfolioItem->delete();
            DB::afterCommit(fn (): bool => Storage::disk($disk)->delete($path));
        });

        return redirect()
            ->route('mentor.students.portfolio.index', $student)
            ->with('status', 'पोर्टफोलिओ पुरावा हटवला.');
    }

    private function portfolioView(Request $request, Student $student): View
    {
        $filters = $request->validate([
            'academic_year_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $student->school_id),
            ],
            'type' => [
                'nullable',
                Rule::in([
                    'pre_test',
                    'post_test',
                    'game',
                    'practice',
                    'simulation',
                    'teacher_observation',
                    'student_work',
                    'certificate',
                    'progress_report',
                ]),
            ],
        ]);
        $items = $student->portfolioItems()
            ->with(['academicYear', 'skill.subject', 'uploader'])
            ->when(
                isset($filters['academic_year_id']),
                fn ($query) => $query->where('academic_year_id', $filters['academic_year_id']),
            )
            ->when(
                isset($filters['type']),
                fn ($query) => $query->where('type', $filters['type']),
            )
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        $skills = Skill::query()
            ->where('is_active', true)
            ->whereHas('subject', fn ($query) => $query
                ->where('is_active', true)
                ->where(fn ($scope) => $scope
                    ->whereNull('school_id')
                    ->orWhere('school_id', $student->school_id)))
            ->with('subject')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('portfolio.index', [
            'student' => $student->load('user'),
            'items' => $items,
            'academicYears' => AcademicYear::query()
                ->whereBelongsTo($student->school)
                ->orderByDesc('starts_on')
                ->get(),
            'skills' => $skills,
            'filters' => $filters,
            'canUpload' => $request->user()->hasRole(RoleCode::Mentor)
                && Gate::allows('create', [PortfolioItem::class, $student]),
        ]);
    }

    private function activeAssignment(
        Request $request,
        Student $student,
        Mentor $mentor,
    ): StudentMentorAssignment {
        return StudentMentorAssignment::query()
            ->whereBelongsTo($student)
            ->whereBelongsTo($mentor)
            ->activeOn(now($request->user()->school->timezone)->toDateString())
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function downloadName(PortfolioItem $portfolioItem, string $extension): string
    {
        $baseName = Str::slug($portfolioItem->title) ?: 'portfolio-item-'.$portfolioItem->id;

        return $extension === '' ? $baseName : "{$baseName}.{$extension}";
    }
}
