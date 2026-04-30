<p>Ciao,</p>

<p>in allegato trovi il contratto <b>#{{ $contract->id }}</b>.</p>

<p>
    Corso: <b>{{ $contract->course?->name ?? '—' }}</b><br>
    Lingua: <b>{{ $contract->language_id ?? '—' }}</b>
</p>

<p>Grazie,<br>A&A Language Center</p>
