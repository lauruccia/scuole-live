<?php

namespace App\Console\Commands;

use App\Models\BillingProfile;
use App\Models\Contract;
use App\Models\CoursePurchase;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\NotificationEmailLog;
use App\Models\Student;
use App\Models\StudentUnsubscribe;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

/**
 * Subject Access Request — Art. 15 GDPR.
 *
 * Esporta in un singolo JSON tutti i dati personali dell'interessato:
 *   - dati anagrafici (User, Student)
 *   - dati di fatturazione (BillingProfile)
 *   - contratti, acquisti, lezioni
 *   - log email inviate
 *   - log GDPR di modifica dati (activity_log)
 *
 * Uso (cron one-off su cPanel):
 *   php artisan school:gdpr-export-user --email=mario.rossi@example.com
 *   php artisan school:gdpr-export-user --student-id=42
 *   php artisan school:gdpr-export-user --user-id=7
 *
 * Output: storage/app/gdpr-exports/{slug}-{timestamp}.json
 */
class GdprExportUser extends Command
{
    protected $signature = 'school:gdpr-export-user
                            {--email= : Email dell\'interessato}
                            {--student-id= : ID record student}
                            {--user-id= : ID record user}';

    protected $description = 'Esporta in JSON tutti i dati personali (Subject Access Request - Art. 15 GDPR)';

    public function handle(): int
    {
        $email     = $this->option('email');
        $studentId = $this->option('student-id');
        $userId    = $this->option('user-id');

        if (! $email && ! $studentId && ! $userId) {
            $this->error('Fornire almeno uno tra --email, --student-id o --user-id.');
            return self::FAILURE;
        }

        // Risoluzione subject
        $user    = $userId ? User::find($userId) : ($email ? User::where('email', $email)->first() : null);
        $student = $studentId ? Student::find($studentId) : null;
        if (! $student && $email) {
            $student = Student::where('email', $email)->first();
        }
        if (! $student && $user) {
            $student = Student::where('user_id', $user->id)->first();
        }

        if (! $user && ! $student) {
            $this->error('Nessun User o Student trovato per i criteri forniti.');
            return self::FAILURE;
        }

        $this->info('Subject identificato:');
        if ($user)    $this->line("  User    #{$user->id} <{$user->email}>");
        if ($student) $this->line("  Student #{$student->id} <{$student->email}>");

        $emails = collect([
            $user?->email,
            $student?->email,
            $email,
        ])->filter()->map(fn ($e) => strtolower(trim($e)))->unique()->values();

        $report = [
            'meta' => [
                'generated_at'    => now()->toIso8601String(),
                'generated_by'    => 'school:gdpr-export-user',
                'criteria'        => array_filter([
                    'email'      => $email,
                    'student_id' => $studentId,
                    'user_id'    => $userId,
                ]),
                'gdpr_article'    => 'Art. 15 - Diritto di accesso',
                'retention_note'  => 'I dati di audit log seguono la retention configurata in config/activitylog.php',
            ],

            'user'             => $user?->toArray(),
            'student'          => $student?->toArray(),

            'billing_profiles' => $emails->isEmpty() ? [] : BillingProfile::query()
                ->whereIn('email', $emails)
                ->get()
                ->toArray(),

            'contracts' => $student
                ? Contract::query()
                    ->whereHas('students', fn ($q) => $q->where('students.id', $student->id))
                    ->get()
                    ->toArray()
                : [],

            'course_purchases' => $emails->isEmpty()
                ? []
                : CoursePurchase::query()
                    ->where(function ($q) use ($emails, $user) {
                        $q->whereIn('billing_email', $emails);
                        if ($user) {
                            $q->orWhere('user_id', $user->id);
                        }
                    })
                    ->get()
                    ->toArray(),

            'lessons' => $student
                ? Lesson::query()
                    ->where('student_id', $student->id)
                    ->select(['id', 'contract_id', 'student_id', 'teacher_id',
                              'starts_at', 'ends_at', 'duration_minutes',
                              'status', 'counts_as_consumed', 'created_at', 'updated_at'])
                    ->get()
                    ->toArray()
                : [],

            'leads' => $emails->isEmpty() ? [] : Lead::query()
                ->whereIn('email', $emails)
                ->get()
                ->toArray(),

            'email_logs' => $emails->isEmpty()
                ? []
                : (Schema::hasTable('notification_email_logs')
                    ? NotificationEmailLog::query()
                        ->whereIn('recipient_email', $emails)
                        ->orderByDesc('id')
                        ->limit(2000)
                        ->get()
                        ->toArray()
                    : []),

            'unsubscribes' => $emails->isEmpty()
                ? []
                : (Schema::hasTable('student_unsubscribes')
                    ? StudentUnsubscribe::query()
                        ->whereIn('email', $emails)
                        ->get()
                        ->toArray()
                    : []),

            'activity_log_about_subject' => $this->collectActivityLog($user, $student),
            'activity_log_caused_by_subject' => $user
                ? Activity::query()
                    ->where('causer_type', \App\Models\User::class)
                    ->where('causer_id', $user->id)
                    ->orderByDesc('id')
                    ->limit(5000)
                    ->get()
                    ->toArray()
                : [],
        ];

        // Slug per filename
        $slug = $student
            ? 'student-' . $student->id
            : 'user-' . ($user?->id ?? 'unknown');

        $dir  = storage_path('app/gdpr-exports');
        $file = $dir . '/' . $slug . '-' . now()->format('Ymd-His') . '.json';

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($file, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $this->info("✓ Export salvato in:");
        $this->line("  {$file}");
        $this->newLine();
        $this->line('Dimensione: ' . number_format(File::size($file) / 1024, 1) . ' KB');
        $this->line('⚠️  Trasmettere all\'interessato in modo sicuro (es. link firmato a scadenza, NON allegato non cifrato).');

        return self::SUCCESS;
    }

    protected function collectActivityLog(?User $user, ?Student $student): array
    {
        $q = Activity::query()->orderByDesc('id')->limit(5000);

        $q->where(function ($qq) use ($user, $student) {
            if ($user) {
                $qq->orWhere(function ($qx) use ($user) {
                    $qx->where('subject_type', \App\Models\User::class)
                       ->where('subject_id', $user->id);
                });
            }
            if ($student) {
                $qq->orWhere(function ($qx) use ($student) {
                    $qx->where('subject_type', \App\Models\Student::class)
                       ->where('subject_id', $student->id);
                });
            }
        });

        return $q->get()->toArray();
    }
}
