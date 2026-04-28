<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Ore Docenti</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }
        .header { background: #f59e0b; color: #fff; padding: 16px 20px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; font-weight: bold; }
        .header p { font-size: 11px; opacity: 0.9; margin-top: 4px; }
        .period-badge { display: inline-block; background: rgba(255,255,255,0.25); border-radius: 4px; padding: 3px 8px; font-size: 11px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead tr { background: #1f2937; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:hover { background: #fef3c7; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        tbody td.right { text-align: right; }
        .compenso { font-weight: bold; color: #92400e; }
        .ore { font-weight: 600; color: #065f46; }
        .totals-row { background: #1f2937 !important; color: #fff; }
        .totals-row td { padding: 9px 10px; font-weight: bold; font-size: 12px; border: none; }
        .footer { margin-top: 20px; font-size: 9px; color: #6b7280; text-align: right; }
        .empty { text-align: center; padding: 30px; color: #9ca3af; font-style: italic; }
        .tariffa { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Report Ore Docenti</h1>
        <p>A&amp;A Language Center</p>
        @if($from || $to)
            <span class="period-badge">
                Periodo: {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : '—' }}
                → {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : '—' }}
            </span>
        @endif
    </div>

    @if($rows->isEmpty())
        <p class="empty">Nessun dato trovato per il periodo selezionato.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Docente</th>
                    <th class="right">Tariffa (€/h)</th>
                    <th class="right">Ore lavorate</th>
                    <th class="right">Compenso lordo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    @php
                        $ore      = (int) ($r->worked_hours_period ?? 0);
                        $tariffa  = (float) ($r->teacher_hourly_rate_gross ?? 0);
                        $compenso = $ore * $tariffa;
                        $label    = trim((string) ($r->teacher_label ?? '')) ?: ('Docente #' . $r->id);
                    @endphp
                    <tr>
                        <td><strong>{{ $label }}</strong></td>
                        <td class="right tariffa">{{ number_format($tariffa, 2, ',', '.') }} €</td>
                        <td class="right ore">{{ $ore }} h</td>
                        <td class="right compenso">{{ number_format($compenso, 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td>TOTALE</td>
                    <td class="right">—</td>
                    <td class="right">{{ $totOre }} h</td>
                    <td class="right">{{ number_format($totCompenso, 2, ',', '.') }} €</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generato il {{ now()->format('d/m/Y H:i') }} — A&amp;A Language Center
    </div>
</body>
</html>
