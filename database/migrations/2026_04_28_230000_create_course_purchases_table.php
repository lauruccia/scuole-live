<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();

            // Corso acquistato
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // Utente autenticato (se registrato al momento dell'acquisto)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Contratto creato dopo conferma pagamento
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();

            // ── Metodo e stato pagamento ──────────────────────────────────────
            $table->enum('payment_method', ['stripe', 'paypal', 'bonifico']);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])
                  ->default('pending');

            // Importo totale pagato (course_price + enrollment_fee)
            $table->decimal('amount', 10, 2);

            // ── ID gateway ───────────────────────────────────────────────────
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('stripe_payment_intent')->nullable();
            $table->string('paypal_order_id')->nullable()->index();
            $table->string('bank_transfer_ref')->nullable(); // riferimento bonifico generato

            // ── Dati fatturazione (snapshot al momento dell'acquisto) ─────────
            $table->enum('billing_type', ['private', 'company'])->default('private');
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_email');
            $table->string('billing_phone')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_country')->nullable()->default('IT');
            $table->string('billing_tax_code')->nullable();
            $table->string('company_name')->nullable();
            $table->string('vat_number')->nullable();

            // ── Nota interna (segreteria) ─────────────────────────────────────
            $table->text('notes')->nullable();

            // ── Timestamp pagamento confermato ─────────────────────────────────
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_purchases');
    }
};
