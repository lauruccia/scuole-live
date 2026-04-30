<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('students', function (Blueprint $table) {
        $table->id();

        $table->string('first_name');
        $table->string('last_name');

        $table->string('email')->nullable()->index();
        $table->string('phone')->nullable();

        $table->date('birth_date')->nullable();
        $table->boolean('is_minor')->default(false);

        // Dati genitore (se minore)
        $table->string('parent_first_name')->nullable();
        $table->string('parent_last_name')->nullable();
        $table->string('parent_email')->nullable();
        $table->string('parent_phone')->nullable();

        $table->text('notes')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
