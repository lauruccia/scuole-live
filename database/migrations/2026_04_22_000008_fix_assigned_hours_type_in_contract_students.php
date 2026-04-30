<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corregge il tipo di assigned_hours da unsignedInteger a decimal(8,2).
 * Il Model ContractStudent casta questo campo come 'decimal:2', ma la colonna
 * era stata creata come unsignedInteger perdendo i decimali al salvataggio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            $table->decimal('assigned_hours', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contract_students', function (Blueprint $table) {
            $table->unsignedInteger('assigned_hours')->nullable()->change();
        });
    }
};
