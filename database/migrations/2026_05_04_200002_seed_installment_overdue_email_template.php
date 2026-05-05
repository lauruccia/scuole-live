<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge il template email per i promemoria rate scadute.
 * Usa updateOrInsert per essere idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_templates')->updateOrInsert(
            ['slug' => 'installment_overdue'],
            [
                'name'          => 'Promemoria rata scaduta',
                'slug'          => 'installment_overdue',
                'trigger_event' => 'installment.overdue',
                'subject'       => 'Promemoria pagamento rata #{{numero_rata}} — {{nome_scuola}}',
                'body_html'     => <<<'HTML'
<p>Gentile {{nome_intestatario}},</p>

<p>Le ricordiamo che la <strong>rata #{{numero_rata}}</strong> del suo contratto è
<strong>in scadenza il {{data_scadenza}}</strong>.</p>

<p><strong>Importo dovuto:</strong> € {{importo}}</p>

<p>Se ha già provveduto al pagamento, la preghiamo di ignorare questa comunicazione.</p>

<p>Per qualsiasi informazione non esiti a contattarci.</p>

<p>Cordiali saluti,</p>
HTML,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('email_templates')->where('slug', 'installment_overdue')->delete();
    }
};
