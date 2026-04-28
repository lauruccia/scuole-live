<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->json('permissions_report')->nullable()->after('name');
            $table->json('permissions_studenti')->nullable()->after('permissions_report');
            $table->json('permissions_didattica')->nullable()->after('permissions_studenti');
            $table->json('permissions_risorse')->nullable()->after('permissions_didattica');
            $table->json('permissions_impostazioni')->nullable()->after('permissions_risorse');
            $table->json('permissions_widget')->nullable()->after('permissions_impostazioni');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn([
                'permissions_report',
                'permissions_studenti',
                'permissions_didattica',
                'permissions_risorse',
                'permissions_impostazioni',
                'permissions_widget',
            ]);
        });
    }
};
