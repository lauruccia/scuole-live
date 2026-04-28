<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class CompitiPage extends Page
{
    use HasStudentScope;
    use WithFileUploads;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Compiti';
    protected static ?string $title           = 'I miei compiti';
    protected static string  $view            = 'filament.studente.pages.compiti-page';
    protected static ?int    $navigationSort  = 4;

    public array  $homeworks       = [];
    public array  $uploadFiles     = [];   // file caricati per ogni compito [homework_id => file]
    public array  $studentNotes    = [];   // note per ogni compito
    public ?int   $student_id      = null;

    public function mount(): void
    {
        $student = $this->getStudent();
        if (! $student) {
            $this->homeworks = [];
            return;
        }

        $this->student_id = $student->id;
        $this->loadHomeworks($student->id);
    }

    private function loadHomeworks(int $studentId): void
    {
        // Carica i compiti dei contratti in cui lo studente è beneficiario
        $contractIds = \App\Models\Contract::query()
            ->whereHas('students', fn ($q) => $q->where('students.id', $studentId))
            ->pluck('id')
            ->toArray();

        if (empty($contractIds)) {
            $this->homeworks = [];
            return;
        }

        $this->homeworks = Homework::with(['teacher', 'submissions' => function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            }])
            ->whereIn('contract_id', $contractIds)
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Homework $hw) {
                $sub = $hw->submissions->first();
                return [
                    'id'           => $hw->id,
                    'title'        => $hw->title,
                    'instructions' => $hw->instructions,
                    'language'     => $hw->language,
                    'due_at'       => $hw->due_at?->format('d/m/Y H:i'),
                    'is_past_due'  => $hw->isPastDue(),
                    'teacher_name' => $hw->teacher?->name ?? '—',
                    'attachment_path' => $hw->attachment_path,
                    'attachment_name' => $hw->attachment_name,
                    'submission'   => $sub ? [
                        'id'               => $sub->id,
                        'status'           => $sub->status,
                        'file_name'        => $sub->file_name,
                        'grade'            => $sub->grade,
                        'teacher_feedback' => $sub->teacher_feedback,
                        'submitted_at'     => $sub->submitted_at?->format('d/m/Y H:i'),
                    ] : null,
                ];
            })
            ->toArray();
    }

    public function submitHomework(int $homeworkId): void
    {
        $student = $this->getStudent();
        if (! $student) return;

        $file = $this->uploadFiles[$homeworkId] ?? null;
        $note = $this->studentNotes[$homeworkId] ?? null;

        if (! $file && ! $note) {
            Notification::make()->title('Carica un file o scrivi una nota')->warning()->send();
            return;
        }

        $filePath = null;
        $fileName = null;
        $fileMime = null;

        if ($file) {
            $filePath = $file->store('homeworks/submissions', 'public');
            $fileName = $file->getClientOriginalName();
            $fileMime = $file->getMimeType();
        }

        HomeworkSubmission::updateOrCreate(
            [
                'homework_id' => $homeworkId,
                'student_id'  => $student->id,
            ],
            [
                'file_path'    => $filePath,
                'file_name'    => $fileName,
                'file_mime'    => $fileMime,
                'student_note' => $note,
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]
        );

        unset($this->uploadFiles[$homeworkId], $this->studentNotes[$homeworkId]);
        $this->loadHomeworks($student->id);

        Notification::make()->title('Compito consegnato!')->success()->send();
    }
}
