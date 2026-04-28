<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge gli indici critici mancanti identificati nell'audit di performance.
 * Questi indici prevengono full table scan nelle query più frequenti del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- lessons ---
        Schema::table('lessons', function (Blueprint $table) {
            // student_id: usato in 15+ query nel LessonGeneratorService (per beneficiario)
            if (! $this->indexExists('lessons', 'lessons_student_id_index')) {
                $table->index('student_id', 'lessons_student_id_index');
            }

            // cancelled_at: usato in whereNull/whereNotNull in quasi tutte le query sulle lezioni
            if (! $this->indexExists('lessons', 'lessons_cancelled_at_index')) {
                $table->index('cancelled_at', 'lessons_cancelled_at_index');
            }

            // counts_as_consumed: usato in WHERE nella recalcConsumedHours e nel delete del generatore
            if (! $this->indexExists('lessons', 'lessons_counts_as_consumed_index')) {
                $table->index('counts_as_consumed', 'lessons_counts_as_consumed_index');
            }

            // starts_at: usato in range query per lezioni future (>= today)
            if (! $this->indexExists('lessons', 'lessons_starts_at_index')) {
                $table->index('starts_at', 'lessons_starts_at_index');
            }

            // Indice composito per la query più comune: lezioni per contratto+studente non cancellate
            if (! $this->indexExists('lessons', 'lessons_contract_student_cancelled_idx')) {
                $table->index(
                    ['contract_id', 'student_id', 'cancelled_at'],
                    'lessons_contract_student_cancelled_idx'
                );
            }
        });

        // --- contract_students ---
        Schema::table('contract_students', function (Blueprint $table) {
            // Indice composito (contract_id, student_id): usato in syncBillingBeneficiaryStudent e lookup
            if (! $this->indexExists('contract_students', 'cs_contract_student_idx')) {
                $table->index(['contract_id', 'student_id'], 'cs_contract_student_idx');
            }
        });

        // --- installments ---
        Schema::table('installments', function (Blueprint $table) {
            // status: usato nei filtri Filament (unpaid, paid, overdue...)
            if (! $this->indexExists('installments', 'installments_status_index')) {
                $table->index('status', 'installments_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex('installments_status_index');
        });

        Schema::table('contract_students', function (Blueprint $table) {
            $table->dropIndex('cs_contract_student_idx');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_contract_student_cancelled_idx');
            $table->dropIndex('lessons_starts_at_index');
            $table->dropIndex('lessons_counts_as_consumed_index');
            $table->dropIndex('lessons_cancelled_at_index');
            $table->dropIndex('lessons_student_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'"))
            ->isNotEmpty();
    }
};
