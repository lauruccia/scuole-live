<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status')
                ->comment('Quando il contratto è stato chiuso');
            $table->string('completion_source', 10)->nullable()->after('completed_at')
                ->comment('auto | manual');
            $table->boolean('auto_complete_disabled')->default(false)->after('completion_source')
                ->comment('true se riaperto a mano: il job notturno non lo richiude da solo');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'completion_source', 'auto_complete_disabled']);
        });
    }
};
