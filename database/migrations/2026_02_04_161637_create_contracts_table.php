<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            // intestazione fattura
            $table->string('billing_type', 20); // private | company

            // privato
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_tax_code')->nullable(); // CF

            // azienda
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable(); // P.IVA
            $table->string('sdi')->nullable();
            $table->string('pec')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_zip')->nullable();
            $table->string('company_country')->nullable();

            // corso acquistato
            $table->unsignedBigInteger('course_id')->nullable(); // se hai tabella courses
            $table->string('language_id')->nullable(); // o subject_id se preferisci
            $table->string('lesson_type')->nullable(); // individuale/gruppo/online ecc

            // date corso (contratto/dettagli)
            $table->date('admission_date')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();

            // importi
            $table->decimal('course_price', 10, 2)->default(0);
            $table->decimal('enrollment_fee', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);

            // pagamento
            $table->string('payment_mode', 20)->default('single'); // single | installments
            $table->unsignedInteger('installments_count')->nullable();
            $table->date('first_installment_date')->nullable();

            // ore
            $table->decimal('hours_purchased', 8, 2)->default(0);
            $table->decimal('hours_consumed', 8, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            // FK course_id aggiunta in migration 2026_05_04_200006_add_course_fk_to_contracts_table
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
