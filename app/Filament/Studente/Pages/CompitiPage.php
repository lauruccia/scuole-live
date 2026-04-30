<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class CompitiPage extends Page
{
    use HasStudentScope;
    use WithFileUploads;

    protected static ?string $navigationIcon  = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Esercitazioni con restituzione';
    protected static ?string $title           = 'Esercitazioni con restituzione';
    protected static string  $view            = 'filament.studente.pages.compiti-page';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int    $navigationSort  = 30;

    public array  $homeworks       = [];
    public array  $studentNotes    = [];   // note per ogni compito
    public ?int   $student_id      = null;

    // File upload: una property per homework_id (stringa chiave = homework id)
    // Livewire v3 + WithFileUploads supporta array con chiavi stringa su file singoli
    public array $uploadedFiles = [];  // ['hwId' => TemporaryUploadedFile]
    public ?int $uploadingForId = null; // homework_id attivo (per mostrare preview)

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
                        'file_path'        => $sub->file_path,
                        'file_name'        => $sub->file_name,
                        'student_note'     => $sub->student_note,
                        'grade'            => $sub->grade,
                        'teacher_feedback' => $sub->teacher_feedback,
                        'submitted_at'     => $sub->submitted_at?->format('d/m/Y H:i'),
                    ] : null,
                ];
            })
            ->toArray();
    }

    public function refreshHomeworks(): void
    {
        if ($this->student_id) {
            $this->loadHomeworks($this->student_id);
        }
    }

    public function setUploadingFor(int $homeworkId): void
    {
        $this->uploadingForId = $homeworkId;
    }

    public function submitHomework(int $homeworkId): void
    {
        $student = $this->getStudent();
        if (! $student) return;

        $file = $this->uploadedFiles[(string) $homeworkId] ?? null;
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

        $updateData = [
            'student_note' => $note,
            'status'       => 'submitted',
            'submitted_at' => now(),
        ];

        // Aggiorna file solo se ne è stato caricato uno nuovo
        if ($filePath) {
            $updateData['file_path'] = $filePath;
            $updateData['file_name'] = $fileName;
            $updateData['file_mime'] = $fileMime;
        }

        HomeworkSubmission::updateOrCreate(
            [
                'homework_id' => $homeworkId,
                'student_id'  => $student->id,
            ],
            $updateData
        );

        unset($this->studentNotes[$homeworkId], $this->uploadedFiles[(string) $homeworkId]);
        $this->uploadingForId = null;
        $this->loadHomeworks($student->id);

        Notification::make()->title('Compito consegnato!')->success()->send();
    }

    /** Aggiorna una consegna già esistente (prima della valutazione) */
    public function updateSubmission(int $homeworkId): void
    {
        $student = $this->getStudent();
        if (! $student) return;

        $sub = HomeworkSubmission::where('homework_id', $homeworkId)
            ->where('student_id', $student->id)
            ->where('status', 'submitted') // solo se non ancora valutata
            ->first();

        if (! $sub) {
            Notification::make()->title('Consegna non modificabile')->warning()->send();
            return;
        }

        $file = $this->uploadedFiles[(string) $homeworkId] ?? null;
        $note = $this->studentNotes[$homeworkId] ?? null;

        $updateData = ['submitted_at' => now()];

        if ($note !== null) {
            $updateData['student_note'] = $note;
        }

        if ($file) {
            $updateData['file_path'] = $file->store('homeworks/submissions', 'public');
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_mime'] = $file->getMimeType();
        }

        $sub->update($updateData);

        unset($this->studentNotes[$homeworkId], $this->uploadedFiles[(string) $homeworkId]);
        $this->loadHomeworks($student->id);

        Notification::make()->title('Consegna aggiornata!')->success()->send();
    }

    /** Annulla una consegna non ancora valutata */
    public function cancelSubmission(int $homeworkId): void
    {
        $student = $this->getStudent();
        if (! $student) return;

        $deleted = HomeworkSubmission::where('homework_id', $homeworkId)
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->delete();

        if (! $deleted) {
            Notification::make()->title('Impossibile annullare')->warning()->send();
            return;
        }

        $this->loadHomeworks($student->id);
        Notification::make()->title('Consegna annullata')->success()->send();
    }
}
