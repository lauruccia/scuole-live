<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Sposta i dati esistenti: contract_id → pivot ─────────────────
        // Prima creiamo la pivot, poi migrare i dati, poi rimuovere le colonne

        Schema::dropIfExists('contract_course_material');
        Schema::create('contract_course_material', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                  ->constrained('contracts')
                  ->cascadeOnDelete();

            $table->foreignId('course_material_id')
                  ->constrained('course_materials')
                  ->cascadeOnDelete();

            // Visibilità per-assegnazione (lo stesso file può essere visibile a uno studente
            // ma non ancora a un altro)
            $table->boolean('is_visible')->default(true);

            $table->timestamp('assigned_at')->useCurrent();

            // Non duplicare la stessa assegnazione
            $table->unique(['contract_id', 'course_material_id']);
        });

        // ── 2. Migra i dati esistenti (contract_id diretto → pivot) ─────────
        if (Schema::hasColumn('course_materials', 'contract_id')) {
            $rows = DB::table('course_materials')
                ->whereNotNull('contract_id')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                DB::table('contract_course_material')->insertOrIgnore([
                    'contract_id'        => $row->contract_id,
                    'course_material_id' => $row->id,
                    'is_visible'         => $row->is_visible ?? 1,
                    'assigned_at'        => now(),
                ]);
            }
        }

        // ── 3. Rimuovi colonne ora gestite dalla pivot ───────────────────────
        Schema::table('course_materials', function (Blueprint $table) {
            if (Schema::hasColumn('course_materials', 'contract_id')) {
                $table->dropForeign(['contract_id']);
                $table->dropColumn('contract_id');
            }
            if (Schema::hasColumn('course_materials', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
        });
    }

    public function down(): void
    {
        // Ripristina le colonne originali
        Schema::table('course_materials', function (Blueprint $table) {
            $table->foreignId('contract_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('contracts')
                  ->nullOnDelete();
            $table->boolean('is_visible')->default(true);
        });

        Schema::dropIfExists('contract_course_material');
    }
};
