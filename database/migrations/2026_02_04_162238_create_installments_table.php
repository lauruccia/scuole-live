<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('contract_id');
            $table->unsignedInteger('number')->default(1); // 0 per acconto, 1..N per rate
            $table->boolean('is_deposit')->default(false);

            $table->date('due_date');
            $table->decimal('amount', 10, 2)->default(0);

            $table->string('status', 20)->default('unpaid'); // unpaid|paid
            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
            $table->index(['contract_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
