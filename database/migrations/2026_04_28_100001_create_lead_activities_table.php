<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // chi ha registrato l'attività

            // Tipo attività
            $table->string('type')->default('note');
            // note | call | email | meeting | whatsapp | status_change

            $table->string('subject')->nullable(); // oggetto breve
            $table->text('body')->nullable();      // corpo / descrizione

            // Per status_change: salviamo i valori prima/dopo
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            // Data/ora effettiva dell'attività (può differire da created_at)
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
