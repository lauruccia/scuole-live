<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge hours_full a contracts e courses.
 *
 * hours_full = quota di ore "Full immersion" incluse nel contratto/corso.
 * Ore personalizzate = hours_purchased - hours_full
 * Il generatore di lezioni usa solo le ore personalizzate per lo slot fisso.
 * Le ore full vengono pianificate on-demand dall'amministrazione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('hours_full', 8, 2)->default(0)->after('hours_purchased')
                ->comment('Ore full immersion incluse nel contratto (pianificate on-demand, non auto-generate).');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('hours_full', 8, 2)->default(0)->after('hours_purchased')
                ->comment('Ore full immersion incluse nel corso (pianificate on-demand).');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('hours_full');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('hours_full');
        });
    }
};
