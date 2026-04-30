<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Dati anagrafici
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();

            // Interesse
            $table->string('course_interest')->nullable(); // nome corso libero
            $table->text('interest_notes')->nullable();

            // Fonte
            $table->string('source')->default('manual'); // manual, website, referral, social, google, other

            // Pipeline
            $table->string('status')->default('new')->index();
            // new | contacted | proposal_sent | enrolled | lost

            $table->text('loss_reason')->nullable(); // compilato quando status = lost

            // Assegnazione interna
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Follow-up
            $table->date('followup_at')->nullable()->index();

            // Note interne
            $table->text('notes')->nullable();

            // Conversione
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
