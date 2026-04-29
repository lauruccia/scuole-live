<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ricevuta Pagamento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #fff;
            padding: 30px 40px;
        }

        /* ── Header ───────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 28px;
            border-bottom: 3px solid #1e3a5f;
            padding-bottom: 16px;
        }
        .header-left  { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: middle; }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: -0.3px;
        }
        .school-sub {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
            line-height: 1.6;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.5px;
        }
        .doc-num {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ── Info box ──────────────────────────────────────── */
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 16px;
        }
        .info-box:last-child { padding-right: 0; padding-left: 16px; }

        .box-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            margin-bottom: 6px;
        }
        .box-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            line-height: 1.7;
            font-size: 11px;
        }
        .box-content strong { color: #1e3a5f; }

        /* ── Tabella rata ──────────────────────────────────── */
        .table-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead tr {
            background: #1e3a5f;
            color: #fff;
        }
        thead th {
            padding: 9px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        thead th.right { text-align: right; }
        tbody td {
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        tbody td.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9fafb; }

        .totale-row td {
            padding: 11px 12px;
            font-weight: bold;
            font-size: 13px;
            background: #f0f4f8;
            border-top: 2px solid #1e3a5f;
        }
        .totale-row td.right { color: #1e3a5f; }

        /* ── Badge stato ───────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.05em;
        }
        .badge-paid    { background: #d1fae5; color: #065f46; }
        .badge-unpaid  { background: #fee2e2; color: #991b1b; }

        /* ── Footer ────────────────────────────────────────── */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.7;
        }

        /* ── Nota di ringraziamento ───────────────────────── */
        .thank-you {
            margin-top: 24px;
            padding: 14px 18px;
            background: #eff6ff;
            border-left: 4px solid #1e3a5f;
            border-radius: 0 6px 6px 0;
            font-size: 12px;
            color: #1e3a5f;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ────────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            <div class="school-name">{{ $schoolName }}</div>
            <div class="school-sub">
                {{ $schoolAddress }}<br>
                Tel {{ $schoolPhone }}
                @if($schoolEmail)
                    &nbsp;·&nbsp; {{ $schoolEmail }}
                @endif
                @if($schoolWebsite)
                    &nbsp;·&nbsp; {{ $schoolWebsite }}
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">RICEVUTA</div>
            <div class="doc-num">
                N° RIC-{{ str_pad($installment->id, 5, '0', STR_PAD_LEFT) }}<br>
                Emessa il: {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    {{-- ── INFO BOXES ──────────────────────────────────────────────────────── --}}
    <div class="info-row">
        <div class="info-box">
            <div class="box-label">Fatturato a</div>
            <div class="box-content">
                <strong>{{ $billingName }}</strong><br>
                @if($billingEmail) {{ $billingEmail }}<br> @endif
                @if($billingPhone) {{ $billingPhone }}<br> @endif
                @if($billingAddress) {{ $billingAddress }} @endif
            </div>
        </div>
        <div class="info-box">
            <div class="box-label">Dettagli contratto</div>
            <div class="box-content">
                <strong>Contratto #{{ $contract->id }}</strong><br>
                @if($courseName) Corso: {{ $courseName }}<br> @endif
                @if($contract->academic_year) Anno: {{ $contract->academic_year }}<br> @endif
                Stato pagamento:
                <span class="badge {{ $installment->status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                    {{ $installment->status === 'paid' ? 'PAGATO' : 'NON PAGATO' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── TABELLA RATA ─────────────────────────────────────────────────────── --}}
    <div class="table-title">Riepilogo pagamento</div>
    <table>
        <thead>
            <tr>
                <th>Descrizione</th>
                <th>Scadenza</th>
                <th>Pagato il</th>
                <th class="right">Importo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $installment->is_deposit ? 'Acconto / Caparra' : "Rata n° {$installment->number}" }}
                    @if($courseName)
                        — {{ $courseName }}
                    @endif
                </td>
                <td>{{ $installment->due_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $installment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="right">€ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="totale-row">
                <td colspan="3">Totale pagato</td>
                <td class="right">€ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── NOTE DI RINGRAZIAMENTO ──────────────────────────────────────────── --}}
    @if($installment->status === 'paid')
    <div class="thank-you">
        Grazie per il pagamento. Questa ricevuta conferma la ricezione dell'importo
        <strong>€ {{ number_format((float) $installment->amount, 2, ',', '.') }}</strong>
        in data <strong>{{ $installment->paid_at?->format('d/m/Y') }}</strong>.
    </div>
    @endif

    {{-- ── FOOTER ──────────────────────────────────────────────────────────── --}}
    <div class="footer">
        {{ $schoolLegalName }} — {{ $schoolFullAddress }}<br>
        @if($bankIban)
            IBAN: {{ $bankIban }}
            @if($bankIntestatario)
                &nbsp;·&nbsp; Intestato a: {{ $bankIntestatario }}
            @endif
            <br>
        @endif
        Documento generato automaticamente il {{ now()->format('d/m/Y \a\l\l\e H:i') }}
        — Non ha valore fiscale ai sensi del D.P.R. 633/72.
    </div>

</body>
</html>
