<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // Ragione sociale

            $table->string('vat_number', 32)->nullable()->index(); // Partita IVA
            $table->string('tax_code', 32)->nullable()->index();   // Codice fiscale azienda (se presente)

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
