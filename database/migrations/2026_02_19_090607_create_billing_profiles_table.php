<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();

            // private | company
            $table->string('type', 20);

            // Se type = company → company_id valorizzato
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            // Se type = private → questi campi valorizzati
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Dati fiscali
            $table->string('fiscal_code', 32)->nullable()->index();
            $table->string('vat_number', 32)->nullable()->index();

            $table->string('sdi_code', 16)->nullable();
            $table->string('pec')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();

            $table->string('address')->nullable();
            $table->string('zip', 16)->nullable();
            $table->string('city')->nullable();
            $table->string('province', 16)->nullable();
            $table->string('country')->nullable()->default('Italia');

            $table->timestamps();

            $table->index(['type', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_profiles');
    }
};
