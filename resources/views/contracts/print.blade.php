<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Contratto #{{ $contract->id }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; line-height: 1.15; }

        @page { size: A4; margin: 6mm; }
        html, body { margin: 0 !important; padding: 0 !important; }

        .page {
            border: 2px solid #1e40af;
            padding: 8px 8px 6px 8px;
            margin: 0 !important;
            page-break-inside: avoid !important;
        }

        .title {
            text-align:center;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: .5px;
            margin: 0 0 10px 0;
            color:#1e3a8a;
        }

        table { width:100%; border-collapse:collapse; }
        .header td { vertical-align: top; }

        .h-small { font-size: 12px; }
        .right { text-align:right; }
        .center { text-align:center; }

        .label-blue { color:#1e3a8a; font-weight: 800; }
        .mb10 { margin-bottom: 4px !important; }
        .mt18 { margin-top: 8px !important; }
        .indent { margin-left: 22px; }

        .line { display:inline-block; border-bottom:1px solid #000; height: 10px !important; vertical-align: bottom; }
        .w40 { width: 40mm; }
        .w50 { width: 50mm; }
        .w90 { width: 90mm; }

        .sigline { height: 10px !important; }
        .siglbl { font-size: 11px; padding-top: 2px !important; }

        .col3 td { width:33.33%; vertical-align: bottom; }
        .bottom3 td { vertical-align: top; padding-top: 6px !important; }
        .field { font-weight: 700; }

        table, tr, td { page-break-inside: avoid !important; }
    </style>
</head>
<body>

@php
    $today = \Illuminate\Support\Carbon::now()->format('d/m/Y');

    $isCompany = (($contract->billing_type ?? 'private') === 'company');

    // Lingua
    $language = trim((string) ($contract->language_id ?? $contract->language ?? ''));
    $language = $language !== '' ? $language : '—';

    // Intestatario (privato)
    $firstName = trim((string) ($contract->billing_first_name ?? ''));
    $lastName  = trim((string) ($contract->billing_last_name ?? ''));
    $fullName  = trim($firstName.' '.$lastName);
    $fullName  = $fullName !== '' ? $fullName : '—';

    $email = trim((string) ($contract->billing_email ?? ''));
    $email = $email !== '' ? $email : '—';

    $phone = trim((string) ($contract->billing_phone ?? ''));
    $phone = $phone !== '' ? $phone : '—';

    $taxCode = trim((string) ($contract->billing_tax_code ?? ''));
    $taxCode = $taxCode !== '' ? $taxCode : '—';

    // Nascita intestatario (privato)
    $birthDate = $contract->billing_birth_date ?? null;
    $birthPlace = trim((string) ($contract->billing_birth_place ?? ''));

    $birthDateLabel = $birthDate ? \Illuminate\Support\Carbon::parse($birthDate)->format('d/m/Y') : '—';
    $birthPlaceLabel = $birthPlace !== '' ? $birthPlace : '—';

    // Residenza (privato)
    $address = trim((string) ($contract->billing_address ?? ''));
    $zip     = trim((string) ($contract->billing_zip ?? ''));
    $city    = trim((string) ($contract->billing_city ?? ''));
    $prov    = trim((string) ($contract->billing_province ?? ''));
    $country = trim((string) ($contract->billing_country ?? ''));

    $residence = trim(
        $address.' '.$zip.' '.$city
        .(trim($prov) !== '' ? ', '.$prov : '')
        .(trim($country) !== '' ? ', '.$country : '')
    );
    $residence = $residence !== '' ? $residence : '—';

    // Corso + tipo lezione
    $courseName = trim((string) ($contract->course?->name ?? ''));
    $courseName = $courseName !== '' ? $courseName : '—';

    $lessonType = trim((string) ($contract->lesson_type ?? ''));
    $lessonTypeNorm = mb_strtolower($lessonType);

    // Ore acquistate
    $hoursPurchased = (float) ($contract->hours_purchased ?? 0);
    $hoursLabel = $hoursPurchased > 0
        ? (rtrim(rtrim(number_format($hoursPurchased, 2, ',', '.'), '0'), ',') . ' ore')
        : null;

    // Importi
    $coursePrice   = (float) ($contract->course_price ?? 0);
    $enrollmentFee = (float) ($contract->enrollment_fee ?? 0);
    $total         = $coursePrice + $enrollmentFee;

    $deposit   = (float) ($contract->deposit ?? 0);
    $residual  = max(0, $total - $deposit);

    $fmt = fn($n) => number_format((float)$n, 2, ',', '.');

    // Rate
    $paymentMode = (string) ($contract->payment_mode ?? 'single');
    $nQuotes     = (int) ($contract->installments_count ?? 0);
    $quoteAmount = ($paymentMode === 'installments' && $nQuotes > 0 && $residual > 0)
        ? round($residual / $nQuotes, 2)
        : null;

    // Tipi lezione (incluso combinato)
    $isCombo       = str_contains($lessonTypeNorm, 'personalizzate + full') || str_contains($lessonTypeNorm, '+ full');
    $isPersonalized= str_contains($lessonTypeNorm, 'personalizzate') && ! $isCombo;
    $isFullImm     = str_contains($lessonTypeNorm, 'full immersion') || str_contains($lessonTypeNorm, 'immersion');
    $isTextExam    = str_contains($lessonTypeNorm, 'test') || str_contains($lessonTypeNorm, 'exam');

    // Beneficiari
    $beneficiaries = $contract->beneficiaries ?? collect();
    $billingIsBeneficiary = (bool) ($contract->billing_is_beneficiary ?? true);
@endphp

<div class="page">
    <div class="title">MODULO DI ISCRIZIONE</div>

    <table class="header mb10">
        <tr>
            <td class="h-small">
                <strong>A&amp;A Language Center S.r.l.</strong><br>
                viale Leonardo da Vinci,193 - 00145 Roma<br>
                e-mail: info@aealanguagecenter.it<br>
                C.F./P.Iva : 09121441001
            </td>

            <td class="center h-small">
                <strong>A&amp;A Language Center</strong>
            </td>

            <td class="right h-small">
                <strong>Trinity College London</strong><br>
                Registered Examination Centre nr. 8241<br>
                <strong>Centro Didattico e iscrizioni in sede</strong><br><br>
                <strong>Roma lì</strong><br>
                {{ $today }}
            </td>
        </tr>
    </table>

    <div class="mb10">
        <span class="label-blue">Lingua:</span> {{ $language }}
    </div>

    <div class="mb10">
        <span class="label-blue">Sottoscrittore:</span>
        <strong>
            @if($isCompany)
                {{ mb_strtoupper((string)($contract->company_name ?? '—')) }}
            @else
                {{ mb_strtoupper($fullName) }}
            @endif
        </strong>
    </div>

    <div class="mb10">
        a) In espressa accettazione di quanto oggi proposto da A&amp;A Language Center Srl, nel presente modulo di iscrizione definito nel
        seguito contratto, vengono messi a disposizione del sottoscrittore/a, nella lingua sopra scelta, entro e non oltre
        <strong>1</strong> mesi a far data da oggi, i seguenti servizi che potranno essere svolti previa mia prenotazione dal
        Lunedì al Venerdì dalle ore 10:00 alle ore 19:00 ed il Sabato dalle ore 9:00 alle ore 13:00 :
    </div>

    <div class="indent mb10">
        <div>
            1. <strong>LEZIONI PERSONALIZZATE</strong> nel Vostro disponibile Centro Didattico
            @if(!$isCombo && $isPersonalized)
                — <strong>{{ $courseName }}</strong>@if($hoursLabel) — {{ $hoursLabel }}@endif
            @endif
        </div>

        <div>
            2. <strong>LEZIONI DI FULL IMMERSION</strong> di piccoli gruppi tenute nel Vostro disponibile Centro Didattico
            @if(!$isCombo && $isFullImm)
                — <strong>{{ $courseName }}</strong>@if($hoursLabel) — {{ $hoursLabel }}@endif
            @endif
        </div>

        <div>
            3. <strong>TEST EXAMINATION</strong> per controllare l’avanzamento dell’apprendimento.
            @if($isTextExam)
                — <strong>{{ $courseName }}</strong>@if($hoursLabel) — {{ $hoursLabel }}@endif
            @endif
        </div>

        <div>
            4. <strong>LEZIONI PERSONALIZZATE + FULL (pacchetto combinato)</strong> nel Vostro disponibile Centro Didattico
            @if($isCombo)
                — <strong>{{ $courseName }}</strong>@if($hoursLabel) — {{ $hoursLabel }}@endif
            @endif
        </div>
    </div>

    <div class="mb10">
        b) per i servizi di cui sopra, il corrispettivo, esente IVA ai sensi dell’art.10 n.20 del D.P.R. 26-10-1972 n. 633, viene fissato in :
        ( <strong>{{ $fmt($total) }}</strong> ) comprensivi di acconto ( <strong>{{ $fmt($deposit) }}</strong> ), a saldo rimanente
        ( <strong>{{ $fmt($residual) }}</strong> ), da versarsi entro 15 giorni dalla sottoscrizione del presente contratto.
    </div>

    <div class="mb10">
        In alternativa il corrispettivo viene fissato in:
        ( <strong>{{ $fmt($total) }}</strong> ) comprensivi di acconto ( <strong>{{ $fmt($deposit) }}</strong> ), a saldo rimanente
        ( <strong>{{ $fmt($residual) }}</strong> )
        @if($paymentMode === 'installments' && $nQuotes > 0 && $quoteAmount !== null)
            da pagarsi in n. ( <strong>{{ $nQuotes }}</strong> ) quote mensili consecutive di ( <strong>{{ $fmt($quoteAmount) }}</strong> ),
        @else
            da pagarsi in n. ( <span class="line w40"></span> ) quote mensili consecutive di ( <span class="line w50"></span> ),
        @endif
        la cui prima dovrà essere versata entro 15 giorni dalla sottoscrizione del presente contratto alla proponente A&amp;A Language Center Srl.
    </div>

    <div class="mb10">c) E’ nulla qualsiasi obbligazione che non risulti scritta sul presente contratto irrevocabile oggi negoziato in sede.</div>

    <div class="mb10">
        d) In caso di mancato inizio del contratto, la proponente potrà acquisire a titolo di penale la somma depositata o da depositare dal
        sottoscrittore/a a titolo di acconto, ritenendo che il sottoscrittore abbia rinunciato ad eseguire il contratto. Nel caso in cui venga data
        esecuzione al contratto, l’importo contrattuale dovrà essere comunque versato come sopra descritto.
    </div>

    <div class="mb10">
        e) Le lezioni e/o le Full Immersion prenotate dal sottoscrittore e non disdette con un preavviso minimo di 24 ore precedenti la
        data e l’orario di prenotazione, verranno considerate come dallo stesso fruite.
    </div>

    <div class="mb10">
        f) D.Lgs 30.06.2003 n.196 “Codice in materia di protezione dei dati personali”: la A&amp;A Language Center Srl si impegna ad utilizzare i
        dati personali acquisiti, esclusivamente per uso interno, strettamente correlato al presente contratto.
        Il sottoscrittore/a, dopo aver preso visione della informativa di cui agli art. 13 e 4 del D.Lgs 30.06.2003 n.196, presta il proprio consenso
        all’intero trattamento dei propri dati personali.
    </div>

    <div class="mb10">g) Foro competente per ogni controversia è esclusivamente quello di Roma</div>

    <div class="mt18 mb10 center">
        <strong>Firma</strong> <span class="line w90"></span>
    </div>

    <table class="col3 mb10">
        <tr>
            <td>
                <div class="sigline"></div>
                <div class="siglbl">firma del preponente</div>
            </td>
            <td style="padding:0 10px;">
                <div class="sigline"></div>
                <div class="siglbl">firma per ricevuta dell'acconto</div>
            </td>
            <td>
                <div class="sigline"></div>
                <div class="siglbl">firma del sottoscrittore</div>
            </td>
        </tr>
    </table>

    <div class="h-small mb10">
        Per accettazione specifica delle clausole a) Oggetto e Termine; b) Condizioni di pagamento del corrispettivo e alternative; c) irrevocabilità
        della proposta; d) Mancato inizio del contratto, clausola penale esecuzione contratto; e) Disdetta delle lezioni e/o Full Immersion; D.Lgs. 196/2003; g) Foro esclusivo Roma.
    </div>

    <div class="mb10">
        <div class="sigline" style="width: 220px;"></div>
        <div class="siglbl">firma del sottoscrittore</div>
    </div>



    {{-- BLOCCO BASSO: intestatario (privato) con nato/a il + nato/a a --}}
    <table class="bottom3">
    <tr>
        <td style="width:40%;">
            @if(!$isCompany)
                <div class="field">Nome</div>
                {{ mb_strtoupper($firstName ?: '—') }}<br><br>

                <div class="field">Nato/a il:</div>
                {{ $birthDateLabel }}<br><br>

                <div class="field">Residente in:</div>
                {{ $residence }}<br><br>

                <div class="field">e-mail:</div>
                {{ $email }}
            @else
                <div class="field">Azienda</div>
                {{ mb_strtoupper((string)($contract->company_name ?? '—')) }}<br><br>

                <div class="field">Sede</div>
                @php
                    $cAddr = trim((string)($contract->company_address ?? ''));
                    $cZip  = trim((string)($contract->company_zip ?? ''));
                    $cCity = trim((string)($contract->company_city ?? ''));
                    $cCtry = trim((string)($contract->company_country ?? ''));
                    $cRes  = trim($cAddr.' '.$cZip.' '.$cCity.(($cCtry!=='')? ', '.$cCtry:''));
                @endphp
                {{ $cRes !== '' ? $cRes : '—' }}<br><br>

                <div class="field">PEC / Email</div>
                {{ trim((string)($contract->pec ?? '')) ?: (trim((string)($contract->company_email ?? '')) ?: '—') }}
            @endif
        </td>

        <td style="width:30%;">
            @if(!$isCompany)
                <div class="field">Cognome</div>
                {{ mb_strtoupper($lastName ?: '—') }}<br><br>

                <div class="field">Nato/a a:</div>
                {{ $birthPlaceLabel }}
            @else
                <div class="field">SDI</div>
                {{ trim((string)($contract->sdi ?? '')) ?: '—' }}<br><br>

                <div class="field">Referente</div>
                {{-- se non hai un referente aziendale nel DB, metti trattino --}}
                —
            @endif
        </td>

        <td style="width:30%;">
            @if(!$isCompany)
                <div class="field">C.F.</div>
                {{ $taxCode }}<br><br>

                <div class="field">Tel</div>
                {{ $phone }}
            @else
                <div class="field">P.IVA</div>
                {{ trim((string)($contract->vat_number ?? '')) ?: '—' }}<br><br>

                <div class="field">Tel</div>
                {{ trim((string)($contract->company_phone ?? '')) ?: '—' }}
            @endif
        </td>
    </tr>
</table>

{{-- Riga IBAN + contatti come nel modello cliente --}}
<div class="mb10" style="margin-top:8px;">
    <strong>IBAN:</strong> IT 39 Q 03440 03218 0000 0017 6700 - Banco Desio<br>
    <strong>Tel.:</strong> +39 06 5743734 - <strong>Tel./Fax</strong> +39 06 57301261 - <strong>Mobile</strong> +39 346 3836175<br>
    <strong>website:</strong> www.aealanguagecenter.it
</div>

</div>
</body>
</html>
