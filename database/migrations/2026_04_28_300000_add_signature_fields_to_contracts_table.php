<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('signature_otp', 8)->nullable()->after('status');
            $table->timestamp('signature_otp_expires_at')->nullable()->after('signature_otp');
            $table->unsignedTinyInteger('signature_otp_attempts')->default(0)->after('signature_otp_expires_at');
            $table->timestamp('signed_at')->nullable()->after('signature_otp_attempts');
            $table->string('signed_ip', 45)->nullable()->after('signed_at');
            $table->string('signed_user_agent')->nullable()->after('signed_ip');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'signature_otp',
                'signature_otp_expires_at',
                'signature_otp_attempts',
                'signed_at',
                'signed_ip',
                'signed_user_agent',
            ]);
        });
    }
};
