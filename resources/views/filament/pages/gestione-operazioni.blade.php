<x-filament::page>

    {{-- Banner informativo --}}
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">📋 Pannello Gestione Operazioni</p>
        <p>
            Qui puoi eseguire operazioni di manutenzione in modo sicuro.
            Le azioni <span class="font-semibold text-danger-700">rosse</span> sono irreversibili e richiedono conferma scritta.
            Le azioni <span class="font-semibold text-warning-700">arancioni</span> modificano dati ma sono reversibili.
            Le azioni <span class="font-semibold text-success-700">verdi</span> sono sicure (solo lettura/ricalcolo).
        </p>
    </div>

    @foreach($this->getOperationsCatalog() as $group)
        @php
            $sectionTone = $this->toneClasses($group['tone'] ?? 'gray');
        @endphp

        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-1 flex items-center gap-2">
                <span>{{ $group['icon'] ?? '' }}</span>
                <span>{{ $group['section'] }}</span>
            </h2>
            <p class="text-sm text-gray-500 mb-3">{{ $group['description'] }}</p>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @foreach($group['operations'] as $op)
                    @php
                        $tone = $this->toneClasses($op['tone'] ?? 'gray');
                        $btnColor = $op['tone'] === 'danger' ? 'danger'
                            : ($op['tone'] === 'warning' ? 'warning'
                            : ($op['tone'] === 'success' ? 'success' : 'primary'));
                    @endphp

                    <div class="rounded-xl border {{ $tone['card'] }} p-4 flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-sm text-gray-900">{{ $op['title'] }}</p>
                            <p class="mt-1 text-xs text-gray-600 leading-relaxed">{{ $op['description'] }}</p>

                            @if(($op['tone'] ?? '') === 'danger')
                                <p class="mt-2 text-xs font-medium text-danger-700 bg-danger-50 border border-danger-200 rounded px-2 py-1 inline-block">
                                    ⚠️ Operazione irreversibile — richiede conferma scritta
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 pt-0.5">
                            <x-filament::button
                                size="sm"
                                color="{{ $btnColor }}"
                                wire:click="mountAction('{{ $op['key'] }}')"
                            >
                                Esegui
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Linee guida operative --}}
    <x-filament::section class="mt-4">
        <x-slot name="heading">💡 Linee guida operative — Guida per l'operatore</x-slot>

        <div class="text-sm text-gray-600 space-y-8">

            {{-- EMERGENZA ORE --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">🚨 Correzione emergenza ore (Solo Amministrazione)</p>

                <div class="space-y-4">

                    <div class="border-l-4 border-warning-400 pl-4 bg-warning-50/40 rounded-r-lg pr-3 py-2">
                        <p class="font-semibold text-gray-800">🔍 Come leggere il pannello diagnostico</p>
                        <p class="mt-1 leading-relaxed">
                            Prima di eseguire qualsiasi correzione, il sistema mostra una tabella di diagnosi con questi valori:
                        </p>
                        <ul class="mt-2 space-y-1 list-none pl-0">
                            <li class="flex gap-2"><span class="text-gray-400 shrink-0">→</span><span><strong>Ore acquistate (contratto):</strong> il totale di ore pattuite nel contratto. Questo è il riferimento di partenza.</span></li>
                            <li class="flex gap-2"><span class="text-gray-400 shrink-0">→</span><span><strong>Ore fruite (passate):</strong> lezioni già svolte con flag <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = 1</code>. Queste non si toccano mai.</span></li>
                            <li class="flex gap-2"><span class="text-gray-400 shrink-0">→</span><span><strong>Lezioni future programmate:</strong> lezioni non annullate con data futura. Questa è la parte che si può correggere.</span></li>
                            <li class="flex gap-2"><span class="text-gray-400 shrink-0">→</span><span><strong>Totale programmato:</strong> fruite + future. Idealmente deve coincidere con le ore acquistate.</span></li>
                            <li class="flex gap-2"><span class="text-gray-400 shrink-0">→</span><span><strong>Ore residue:</strong> acquistate meno fruite. Indica quante ore rimangono ancora da erogare.</span></li>
                        </ul>
                        <p class="mt-2 leading-relaxed">
                            Il pannello evidenzia automaticamente le anomalie:
                            <span class="text-danger-700 font-medium">sfondo rosso = lezioni in ECCESSO</span> (il totale programmato supera le ore acquistate),
                            <span class="text-warning-700 font-medium">sfondo giallo = ore MANCANTI</span> (ci sono ore residue non ancora generate come lezioni),
                            <span class="text-success-700 font-medium">sfondo verde = tutto corretto</span>.
                        </p>
                    </div>

                    <div class="border-l-4 border-success-400 pl-4">
                        <p class="font-semibold text-gray-800">✅ Rigenera lezioni mancanti — quando le ore residue non sono state generate</p>
                        <p class="mt-1 leading-relaxed">
                            Genera le lezioni mancanti partendo dagli slot configurati sul contratto, senza toccare quelle già esistenti.
                            Serve quando il saldo mostra ore residue positive ma non ci sono abbastanza lezioni future programmate per coprirle.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Esempio tipico:</strong> contratto da 20h, 10h già fruite, ma future programmate solo 5h invece di 10h.
                            Il sistema genera le lezioni mancanti seguendo il calendario degli slot (giorno/ora/durata).
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Controindicazioni:</strong> se gli slot non sono configurati sul contratto, non verrà generato niente —
                            configura prima gli slot nella sezione "Slot Lezioni" del contratto, poi esegui questa operazione.
                        </p>
                        <p class="mt-1 text-success-700 font-medium">✅ Non elimina nessuna lezione esistente.</p>
                    </div>

                    <div class="border-l-4 border-danger-400 pl-4">
                        <p class="font-semibold text-gray-800">🗑️ Elimina lezioni future in eccesso — IRREVERSIBILE</p>
                        <p class="mt-1 leading-relaxed">
                            Confronta le ore residue disponibili con le lezioni future ordinate per data.
                            Mantiene le prime lezioni che "entrano" nelle ore residue ed elimina definitivamente quelle in eccesso.
                            L'ordine è cronologico: si eliminano le lezioni più lontane nel tempo.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Esempio:</strong> residue 3h, future programmate 5h (5 lezioni da 1h).
                            Il sistema mantiene le prime 3 lezioni ed elimina le ultime 2.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla rispetto ad "Annulla":</strong> usa Elimina solo se sei certo che le lezioni
                            in eccesso non debbano lasciare nessuna traccia (es. generate per errore di sistema).
                            Se invece vuoi mantenere uno storico tracciabile di cosa è stato rimosso e perché, usa "Annulla".
                        </p>
                        <p class="mt-1 text-danger-700 font-medium">🔴 IRREVERSIBILE — i record vengono cancellati dal database, non recuperabili.</p>
                    </div>

                    <div class="border-l-4 border-warning-400 pl-4">
                        <p class="font-semibold text-gray-800">🚫 Annulla lezioni future in eccesso — Consigliato rispetto all'eliminazione</p>
                        <p class="mt-1 leading-relaxed">
                            Stessa logica dell'eliminazione (mantiene le prime lezioni che rientrano nelle ore residue, annulla le restanti),
                            ma invece di cancellare i record li marca come annullati con data, utente e motivo salvati nel database.
                            Le lezioni annullate non compaiono nel calendario attivo, non scalano ore, non generano recuperi.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Perché è preferibile all'eliminazione:</strong> se in futuro si vuole capire cosa è successo
                            (es. audit, contestazione dello studente, ispezione), le lezioni annullate rimangono nello storico
                            con il motivo scritto dall'operatore. Le lezioni eliminate spariscono senza traccia.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Come si reversa:</strong> usa "Riattiva lezioni annullate" nella sezione Operazioni su Contratto.
                        </p>
                        <p class="mt-1 text-warning-700 font-medium">⚠️ Reversibile tramite "Riattiva lezioni annullate". Il motivo inserito viene salvato su ogni lezione.</p>
                    </div>

                    <div class="border-l-4 border-blue-400 pl-4">
                        <p class="font-semibold text-gray-800">🔢 Ricalcola ore fruite — Solo aggiorna il contatore</p>
                        <p class="mt-1 leading-relaxed">
                            Non modifica nessuna lezione. Ricalcola solo il campo numerico "Ore fruite" del contratto
                            sommando le durate delle lezioni con <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = 1</code>.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> il contatore appare sfasato rispetto alle lezioni effettivamente svolte
                            ma le lezioni stesse sono corrette. Può capitare dopo interventi manuali sul database o dopo
                            aver modificato la durata di una lezione già svolta senza ricalcolare.
                        </p>
                        <p class="mt-1 text-success-700 font-medium">✅ Completamente sicura — aggiorna solo il numero visualizzato, nessun dato di lezione viene toccato.</p>
                    </div>

                    <div class="border-l-4 border-purple-400 pl-4">
                        <p class="font-semibold text-gray-800">✏️ Correggi manualmente le ore acquistate</p>
                        <p class="mt-1 leading-relaxed">
                            Modifica il valore "Ore acquistate" nel contratto e ricalcola le ore fruite di conseguenza.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando le ore nel contratto cartaceo/firmato dallo studente differiscono
                            da quelle inserite nel sistema (es. errore di inserimento al momento della creazione del contratto).
                            <em>Non</em> usarla per correggere eccessi di lezioni generate: usa prima le opzioni di annullamento/eliminazione
                            lezioni, poi ricalcola.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Controindicazioni:</strong> modificare le ore acquistate non genera né elimina lezioni automaticamente —
                            dopo la correzione potrebbe essere necessario rigenarare le lezioni mancanti o annullare quelle in eccesso.
                        </p>
                        <p class="mt-1 text-purple-700 font-medium">⚠️ Il motivo inserito viene registrato nella notifica di conferma per audit interno. Documenta sempre la motivazione.</p>
                    </div>

                </div>
            </div>

            {{-- SEZIONE CONTRATTO --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">📄 Operazioni su Contratto</p>

                <div class="space-y-4">

                    <div class="border-l-4 border-warning-400 pl-4">
                        <p class="font-semibold text-gray-800">🔄 Rigenera lezioni contratto</p>
                        <p class="mt-1 leading-relaxed">
                            Cancella fisicamente tutte le lezioni future non ancora svolte (quelle con <code class="bg-gray-100 px-1 rounded text-xs">starts_at ≥ oggi</code>,
                            <code class="bg-gray-100 px-1 rounded text-xs">cancelled_at = NULL</code> e
                            <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = 0</code>) e le ricrea da zero
                            leggendo gli slot orari configurati sul contratto.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> ogni volta che modifichi gli slot orari di un contratto già attivo
                            (es. cambi il giorno, l'ora o la durata di una lezione) e vuoi che il calendario rispecchi
                            la nuova configurazione. Utile anche quando un contratto è stato creato ma le lezioni
                            non sono state generate automaticamente per un problema tecnico.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Opzione "Force":</strong> normalmente il generatore parte dalla data di inizio contratto,
                            ma se tale data è nel passato e non ci sono già lezioni, il sistema non genera nulla per sicurezza.
                            Attivare "Force" forza la generazione ignorando questo controllo — usare con cautela.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Differenza con "Correggi ore contratto → Rigenera mancanti":</strong>
                            questa operazione <em>prima elimina</em> le future esistenti, poi le ricrea tutte.
                            L'opzione nell'emergenza ore invece <em>aggiunge solo</em> le mancanti senza toccare quelle già presenti.
                        </p>
                        <p class="mt-1 text-warning-700 font-medium">
                            ⚠️ Le lezioni future non svolte vengono eliminate prima di essere ricreate. Le lezioni già svolte non vengono mai toccate.
                        </p>
                    </div>

                    <div class="border-l-4 border-danger-400 pl-4">
                        <p class="font-semibold text-gray-800">🗑️ Elimina lezioni future (non svolte) — Contratto</p>
                        <p class="mt-1 leading-relaxed">
                            Rimuove definitivamente dal database tutte le lezioni future non svolte e non annullate del contratto selezionato.
                            I record vengono cancellati: non esiste uno storico, non è possibile recuperarli.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando vuoi svuotare completamente il calendario futuro di un contratto
                            per poi rigenerarlo da capo con configurazione diversa, oppure quando un contratto viene chiuso
                            anticipatamente e le lezioni future non devono lasciare traccia.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Differenza con "Annulla definitivamente":</strong> questa operazione <em>elimina</em> i record dal database —
                            spariscono dal calendario e dallo storico. "Annulla definitivamente" invece <em>conserva</em> i record
                            marcandoli come annullati, così rimangono visibili nello storico con data e motivo di annullamento.
                        </p>
                        <p class="mt-1 text-danger-700 font-medium">
                            🔴 IRREVERSIBILE — richiede la digitazione di ELIMINA per confermare. Le lezioni eliminate non sono recuperabili.
                        </p>
                    </div>

                    <div class="border-l-4 border-danger-400 pl-4">
                        <p class="font-semibold text-gray-800">🚫 Annulla definitivamente lezioni future — Contratto</p>
                        <p class="mt-1 leading-relaxed">
                            Imposta <code class="bg-gray-100 px-1 rounded text-xs">cancelled_at = adesso</code>,
                            <code class="bg-gray-100 px-1 rounded text-xs">is_recoverable = false</code> e
                            <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = false</code>
                            su tutte le lezioni future non svolte del contratto. Le lezioni rimangono nel database come record annullati —
                            non scalano ore, non generano recupero, non appaiono nel calendario attivo.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando le lezioni generate superano le ore acquistate (es. contratto da 10h con 8 lezioni da 1,5h = 12h pianificate)
                            e vuoi rimuovere quelle in eccesso mantenendo uno storico. Oppure quando uno studente interrompe il corso a metà
                            e vuoi "sigillare" le lezioni rimanenti con un motivo documentato.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Come si reversa:</strong> usa l'operazione verde <em>"Riattiva lezioni annullate"</em> per
                            ripristinare in blocco tutte le lezioni annullate future dello stesso contratto.
                        </p>
                        <p class="mt-1 text-warning-700 font-medium">
                            ⚠️ Reversibile tramite "Riattiva lezioni annullate". Richiede comunque un'azione separata per annullare l'effetto.
                        </p>
                    </div>

                    <div class="border-l-4 border-success-400 pl-4">
                        <p class="font-semibold text-gray-800">✅ Riattiva lezioni annullate — Contratto</p>
                        <p class="mt-1 leading-relaxed">
                            Rimuove l'annullamento da tutte le lezioni future con
                            <code class="bg-gray-100 px-1 rounded text-xs">cancelled_at NOT NULL</code> e
                            <code class="bg-gray-100 px-1 rounded text-xs">starts_at ≥ oggi</code>,
                            riportandole allo stato attivo. Azzera <code class="bg-gray-100 px-1 rounded text-xs">cancelled_at</code>,
                            <code class="bg-gray-100 px-1 rounded text-xs">cancelled_by</code> e
                            <code class="bg-gray-100 px-1 rounded text-xs">cancellation_reason</code>.
                            Dopo la riattivazione ricalcola automaticamente le ore fruite del contratto.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> come contromossa di "Annulla definitivamente" se ti accorgi
                            di aver annullato per errore, o se lo studente riprende il corso dopo una pausa.
                        </p>
                        <p class="mt-1 text-blue-700 font-medium">
                            ℹ️ Riattiva <em>tutte</em> le lezioni annullate future del contratto, incluse quelle annullate singolarmente in precedenza. Controlla sempre l'anteprima prima di confermare.
                        </p>
                    </div>

                    <div class="border-l-4 border-success-400 pl-4">
                        <p class="font-semibold text-gray-800">🔢 Ricalcola ore consumate — Contratto</p>
                        <p class="mt-1 leading-relaxed">
                            Rilegge tutte le lezioni del contratto con
                            <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = 1</code>
                            e riscrive il campo <code class="bg-gray-100 px-1 rounded text-xs">hours_consumed</code>
                            sommando i minuti reali di ogni lezione (<code class="bg-gray-100 px-1 rounded text-xs">duration_minutes / 60</code>).
                            Gestisce correttamente lezioni da 30 min, 1h, 1h 30min e 2h.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> se il contatore "Ore fruite" mostrato nel contratto non corrisponde
                            alle lezioni effettivamente svolte. Può capitare dopo interventi manuali sul database,
                            dopo import di dati o dopo aver corretto retroattivamente la durata di alcune lezioni.
                        </p>
                        <p class="mt-1 text-success-700 font-medium">
                            ✅ Operazione completamente sicura — nessuna lezione viene cancellata o modificata. Aggiorna solo il contatore.
                        </p>
                    </div>

                </div>
            </div>

            {{-- SEZIONE STUDENTE --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">👤 Operazioni su Studente</p>

                <div class="space-y-4">

                    <div class="border-l-4 border-danger-400 pl-4">
                        <p class="font-semibold text-gray-800">🗑️ Elimina lezioni future (non svolte) — Studente</p>
                        <p class="mt-1 leading-relaxed">
                            Identica all'operazione per contratto, ma agisce su <em>tutti i contratti</em> dello studente in un colpo solo.
                            Rimuove definitivamente dal database tutte le lezioni future non svolte e non annullate dello studente
                            su qualsiasi contratto. Dopo l'eliminazione ricalcola le ore fruite per ogni contratto interessato.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando uno studente abbandona definitivamente la scuola e
                            vuoi pulire completamente il suo calendario senza lasciare tracce, oppure prima di
                            rigenerare le lezioni su più contratti contemporaneamente.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Attenzione ai contratti multipli:</strong> se lo studente è beneficiario su più contratti (es. corso Inglese + corso Spagnolo),
                            l'operazione agisce su tutti. Usa l'operazione per singolo contratto se vuoi intervenire su uno solo.
                        </p>
                        <p class="mt-1 text-danger-700 font-medium">
                            🔴 IRREVERSIBILE — richiede la digitazione di ELIMINA. Agisce su tutti i contratti attivi dello studente.
                        </p>
                    </div>

                    <div class="border-l-4 border-danger-400 pl-4">
                        <p class="font-semibold text-gray-800">🚫 Annulla definitivamente lezioni future — Studente</p>
                        <p class="mt-1 leading-relaxed">
                            Come l'operazione per contratto, ma agisce su tutti i contratti dello studente.
                            Annulla tutte le lezioni future non svolte impostando
                            <code class="bg-gray-100 px-1 rounded text-xs">cancelled_at</code>,
                            senza scalare ore e senza generare recuperi. Il motivo dell'annullamento
                            viene registrato su ogni lezione.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando uno studente sospende il corso (es. malattia prolungata, partenza)
                            e vuoi bloccare tutte le lezioni future mantenendo lo storico.
                            Diversamente dall'eliminazione, le lezioni restano visibili nell'archivio con
                            data e motivo della cancellazione.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Come si reversa:</strong> usa <em>"Riattiva lezioni annullate — Studente"</em>.
                        </p>
                    </div>

                    <div class="border-l-4 border-success-400 pl-4">
                        <p class="font-semibold text-gray-800">✅ Riattiva lezioni annullate — Studente</p>
                        <p class="mt-1 leading-relaxed">
                            Ripristina tutte le lezioni future annullate dello studente su tutti i contratti.
                            Funziona esattamente come la versione per contratto, ma l'ambito è più ampio:
                            trova le lezioni annullate su qualsiasi contratto associato allo studente e le riattiva tutte.
                            Ricalcola le ore fruite per ogni contratto interessato.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> quando lo studente riprende le lezioni dopo una sospensione
                            gestita con "Annulla definitivamente — Studente".
                        </p>
                        <p class="mt-1 text-blue-700 font-medium">
                            ℹ️ Riattiva tutte le lezioni future annullate dello studente, incluse quelle annullate singolarmente prima dell'operazione in blocco. Controlla sempre l'anteprima prima di confermare.
                        </p>
                    </div>

                </div>
            </div>

            {{-- SEZIONE SISTEMA --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">⚙️ Operazioni di Sistema</p>

                <div class="space-y-4">

                    <div class="border-l-4 border-success-400 pl-4">
                        <p class="font-semibold text-gray-800">🔧 Bonifica ore fruite — Sistema</p>
                        <p class="mt-1 leading-relaxed">
                            Operazione globale che agisce su <em>tutti i contratti</em> del sistema.
                            Riallinea i flag <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed</code> e
                            <code class="bg-gray-100 px-1 rounded text-xs">is_recoverable</code> delle lezioni
                            in base alle regole di business (lezione passata non annullata → counts_as_consumed = 1),
                            poi ricalcola <code class="bg-gray-100 px-1 rounded text-xs">hours_consumed</code> su ogni contratto.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> dopo un import massivo di dati, dopo una migrazione del database,
                            o quando noti che molti contratti hanno il contatore ore sfasato.
                            Non è un'operazione quotidiana — serve per bonificare situazioni
                            di disallineamento globale causate da eventi eccezionali.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Opzione Dry-run:</strong> esegue tutti i calcoli e mostra i risultati nel log
                            senza salvare nulla nel database. Usa sempre il dry-run prima di applicare la bonifica reale,
                            per verificare quanti record verrebbero toccati.
                        </p>
                        <p class="mt-1 text-success-700 font-medium">
                            ✅ La modalità normale corregge solo valori sbagliati. Il dry-run è completamente non-distruttivo.
                        </p>
                    </div>

                    <div class="border-l-4 border-warning-400 pl-4">
                        <p class="font-semibold text-gray-800">📅 Fix lezioni future errate — Sistema</p>
                        <p class="mt-1 leading-relaxed">
                            Corregge uno specifico tipo di anomalia: lezioni con
                            <code class="bg-gray-100 px-1 rounded text-xs">starts_at ≥ oggi</code>,
                            non annullate, ma con
                            <code class="bg-gray-100 px-1 rounded text-xs">counts_as_consumed = 1</code>.
                            Questo stato è incoerente — una lezione futura non può essere già "consumata" —
                            e causa un gonfiamento artificiale delle ore fruite.
                            L'operazione reimposta il flag a <code class="bg-gray-100 px-1 rounded text-xs">0</code>
                            e ricalcola le ore di ogni contratto interessato.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Quando usarla:</strong> se noti che un contratto mostra più ore fruite
                            del previsto rispetto alle lezioni effettivamente svolte, specialmente dopo
                            aver spostato lezioni da una data passata a una futura tramite modifica manuale.
                        </p>
                        <p class="mt-1 leading-relaxed">
                            <strong>Opzione Dry-run:</strong> analizza e stampa nei log le lezioni che verrebbero
                            corrette, senza applicare alcuna modifica. Consigliato prima di eseguire in produzione.
                        </p>
                    </div>

                </div>
            </div>

            {{-- GUIDA RAPIDA SCENARI --}}
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">🗺️ Quale operazione usare? — Guida rapida per scenario</p>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-100 text-gray-600 uppercase">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Situazione rilevata</th>
                                <th class="px-3 py-2 text-left font-semibold">Operazione consigliata</th>
                                <th class="px-3 py-2 text-left font-semibold">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-white">
                                <td class="px-3 py-2">Contratto con ore residue ma nessuna lezione futura</td>
                                <td class="px-3 py-2 text-warning-700 font-medium">Correggi ore → Rigenera mancanti</td>
                                <td class="px-3 py-2 text-gray-500">Verifica prima che gli slot siano configurati</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2">Lezioni in eccesso rispetto alle ore del contratto</td>
                                <td class="px-3 py-2 text-warning-700 font-medium">Correggi ore → Annulla eccesso</td>
                                <td class="px-3 py-2 text-gray-500">Preferisci Annulla (tracciabile) a Elimina</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-3 py-2">Ho modificato gli slot (giorno/ora) e voglio aggiornare il calendario</td>
                                <td class="px-3 py-2 text-warning-700 font-medium">Rigenera lezioni contratto</td>
                                <td class="px-3 py-2 text-gray-500">Elimina e ricrea tutte le future non svolte</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2">Studente interrompe definitivamente il corso</td>
                                <td class="px-3 py-2 text-danger-700 font-medium">Annulla definitivamente — Studente</td>
                                <td class="px-3 py-2 text-gray-500">Mantiene lo storico con motivo scritto</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-3 py-2">Studente riprende dopo pausa/sospensione</td>
                                <td class="px-3 py-2 text-success-700 font-medium">Riattiva lezioni annullate — Studente</td>
                                <td class="px-3 py-2 text-gray-500">Ripristina tutte le future annullate</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2">Il contatore "Ore fruite" non quadra (solo il numero)</td>
                                <td class="px-3 py-2 text-success-700 font-medium">Ricalcola ore consumate</td>
                                <td class="px-3 py-2 text-gray-500">Sicuro, non tocca le lezioni</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-3 py-2">Le ore nel contratto erano sbagliate dall'inizio</td>
                                <td class="px-3 py-2 text-purple-700 font-medium">Correggi ore → Correggi ore acquistate</td>
                                <td class="px-3 py-2 text-gray-500">Poi valuta se rigenarare/annullare lezioni</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2">Molti contratti con ore sbagliate dopo un import</td>
                                <td class="px-3 py-2 text-success-700 font-medium">Bonifica ore fruite — Sistema</td>
                                <td class="px-3 py-2 text-gray-500">Usa prima il dry-run per vedere cosa cambia</td>
                            </tr>
                            <tr class="bg-white">
                                <td class="px-3 py-2">Lezioni future marcate erroneamente come consumate</td>
                                <td class="px-3 py-2 text-warning-700 font-medium">Fix lezioni future errate — Sistema</td>
                                <td class="px-3 py-2 text-gray-500">Usa prima il dry-run</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- NOTE GENERALI --}}
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-xs text-gray-500 space-y-2">
                <p class="font-semibold text-gray-600 mb-2">📌 Note generali per l'operatore</p>
                <p>• <strong>Gestione Operazioni non sostituisce la gestione quotidiana</strong> — annullare una singola lezione, segnarne una come svolta o creare un recupero si fa direttamente dalla lista Lezioni o dal pannello del Contratto.</p>
                <p>• <strong>Corso 10h con slot da 1,5h:</strong> il sistema genera 6 lezioni da 1,5h (9h) più una lezione di completamento da 60 min (1h) per raggiungere le 10h esatte. La lezione di completamento ha una nota nel campo "Note".</p>
                <p>• <strong>Le anteprime sono calcolate in tempo reale</strong> — mostrano esattamente le lezioni che verranno toccate al momento della selezione. Se nel frattempo un'altra persona modifica i dati, chiudi e riapri il pannello per aggiornare l'anteprima.</p>
                <p>• <strong>Tutte le operazioni che modificano lezioni</strong> ricalcolano automaticamente le ore fruite dei contratti coinvolti al termine dell'operazione.</p>
                <p>• <strong>Il campo "Motivo intervento"</strong> è obbligatorio nelle operazioni di emergenza. Scrivi sempre una descrizione chiara: sarà utile in caso di audit o contestazioni future (es. "Lezioni duplicate per errore di sistema — corrette manualmente il 27/04/2026").</p>
                <p>• <strong>In caso di dubbio</strong> sugli effetti di un'operazione, usa sempre l'opzione Dry-run dove disponibile, oppure contatta il supporto tecnico prima di procedere.</p>
            </div>

        </div>
    </x-filament::section>

    {{-- OBBLIGATORIO: Filament stampa i modali delle Actions qui --}}
    <x-filament-actions::modals />

</x-filament::page>
