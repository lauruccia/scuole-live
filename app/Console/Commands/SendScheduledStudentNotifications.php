<?php

namespace App\Console\Commands;

use App\Mail\StudentCommunicationMail;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\NotificationEmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledStudentNotifications extends Command
{
    protected $signature = 'school:send-student-notifications';
    protected $description = 'Invia notifiche automatiche agli studenti per rate in scadenza e fine corso';

    public function handle(): int
    {
        $this->info('Invio notifiche studenti avviato...');

        $today = now()->startOfDay();
        $installmentTargetDate = $today->copy()->addDays(5)->toDateString();
        $courseEndTargetDate = $today->copy()->addDays(20)->toDateString();

        $sentCount = 0;

        /*
        |--------------------------------------------------------------------------
        | 1. RATE IN SCADENZA TRA 5 GIORNI
        |--------------------------------------------------------------------------
        */
        $installments = Installment::query()
            ->with(['contract.students'])
            ->whereDate('due_date', $installmentTargetDate)
            ->get();

        foreach ($installments as $installment) {
            $contract = $installment->contract;

            if (! $contract) {
                continue;
            }

            foreach ($contract->students as $student) {
                $email = $student->email ?? null;

                if (! $email) {
                    continue;
                }

                $alreadySent = NotificationEmailLog::query()
                    ->where('type', 'installment_due_5_days')
                    ->where('student_id', $student->id)
                    ->where('contract_id', $contract->id)
                    ->where('installment_id', $installment->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
                $studentName = $studentName !== '' ? $studentName : 'Studente';

                $subject = 'Promemoria: rata in scadenza tra 5 giorni';

                $htmlBody = '
                    <p>ti ricordiamo che una rata del tuo corso sarà in scadenza il <strong>' . optional($installment->due_date)->format('d/m/Y') . '</strong>.</p>
                    <p>Ti invitiamo a verificare la scadenza e a procedere con il pagamento entro la data prevista.</p>
                    <p>Per qualsiasi informazione puoi contattare la segreteria.</p>
                ';

                Mail::to($email)->send(
                    new StudentCommunicationMail(
                        studentName: $studentName,
                        subjectLine: $subject,
                        htmlBody: $htmlBody
                    )
                );

                NotificationEmailLog::create([
                    'type' => 'installment_due_5_days',
                    'student_id' => $student->id,
                    'installment_id' => $installment->id,
                    'contract_id' => $contract->id,
                    'reference_date' => $installment->due_date,
                    'email' => $email,
                    'sent_at' => now(),
                ]);

                $sentCount++;
                $this->info("Email rata inviata a {$email}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. FINE CORSO TRA 20 GIORNI
        |--------------------------------------------------------------------------
        */
        $contracts = Contract::query()
            ->with(['students', 'course'])
            ->whereDate('ends_at', $courseEndTargetDate)
            ->get();

        foreach ($contracts as $contract) {
            foreach ($contract->students as $student) {
                $email = $student->email ?? null;

                if (! $email) {
                    continue;
                }

                $alreadySent = NotificationEmailLog::query()
                    ->where('type', 'course_end_20_days')
                    ->where('student_id', $student->id)
                    ->where('contract_id', $contract->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
                $studentName = $studentName !== '' ? $studentName : 'Studente';

                $courseName = $contract->course->name ?? 'corso';

                $subject = 'Promemoria: termine lezioni del corso tra 20 giorni';

                $htmlBody = '
                    <p>ti informiamo che il tuo corso <strong>' . e($courseName) . '</strong> terminerà il <strong>' . optional($contract->ends_at)->format('d/m/Y') . '</strong>.</p>
                    <p>Mancano 20 giorni alla conclusione delle lezioni.</p>
                    <p>Per informazioni sul rinnovo, sulla prosecuzione o su nuovi percorsi formativi, puoi contattare la segreteria.</p>
                ';

                Mail::to($email)->send(
                    new StudentCommunicationMail(
                        studentName: $studentName,
                        subjectLine: $subject,
                        htmlBody: $htmlBody
                    )
                );

                NotificationEmailLog::create([
                    'type' => 'course_end_20_days',
                    'student_id' => $student->id,
                    'installment_id' => null,
                    'contract_id' => $contract->id,
                    'reference_date' => $contract->ends_at,
                    'email' => $email,
                    'sent_at' => now(),
                ]);

                $sentCount++;
                $this->info("Email fine corso inviata a {$email}");
            }
        }

        $this->info("Operazione completata. Email inviate: {$sentCount}");

        return self::SUCCESS;
    }
}