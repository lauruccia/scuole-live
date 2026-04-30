<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label', 100)->default('Scuola');
            $table->string('email', 190)->nullable();
            $table->string('calendar_id', 190)->nullable(); // es: primary o id calendario condiviso
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_accounts');
    }
};
