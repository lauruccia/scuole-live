# ScuoleLive — Audit ruoli e permessi

> Aggiornato 13/07/2026 (aggiunto TeacherProfileResource — gruppo "Sito web")

---

## 1. Ruoli ufficiali (in uso)

| Ruolo (slug) | Pannello principale | Pannelli accessibili | Cosa può fare |
|--------------|---------------------|----------------------|---------------|
| `superadmin` | Superadmin | Tutti | Tutto, comandi di sistema, impostazioni avanzate |
| `Amministrazione` | Admin | Admin | Contratti, studenti, lezioni, paghe, pagamenti, settings |
| `Segreteria` | Admin | Admin | Contratti, studenti, lezioni, ore docenti (no paghe né dati fiscali) |
| `Docente` | Docente | Docente | Calendario, materiali, compiti, propri studenti |
| `Studente` | Studente | Studente | Prossima lezione, contratto, materiali, compiti, quiz, rate |

I ruoli sono case-sensitive su Spatie Permission.

---

## 2. Matrice permessi per funzione

| Funzione | Superadmin | Amministrazione | Segreteria | Docente | Studente |
|----------|:---------:|:---------------:|:----------:|:-------:|:--------:|
| GestioneOperazioni | ✅ | ✅ | ❌ | ❌ | ❌ |
| Comandi sistema (SuperadminCommands) | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Audit log (ActivityResource)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Google scuola (GoogleSettings)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Permessi (PermissionResource)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Ruoli (RoleResource)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Log invii email (NotificationEmailLogResource)** | ✅ | ❌ | ❌ | ❌ | ❌ |
| TeacherPayReport (paghe) | ✅ | ✅ | ❌ | ❌ | ❌ |
| TeacherHoursReport | ✅ | ✅ | ✅ | ❌ | ❌ |
| TeacherResource (Docenti) | ✅ | ✅ | ❌ | ❌ | ❌ |
| AnomalyReport (Controllo anomalie) | ✅ | ✅ | ❌ | ❌ | ❌ |
| StudentResource | ✅ | ✅ | ✅ | ❌ | ❌ |
| ContractResource | ✅ | ✅ | ✅ | ❌ | ❌ |
| LessonResource | ✅ | ✅ | ✅ | ❌ | ❌ |
| PaymentsReport | ✅ | ✅ | ✅ | ❌ | ❌ |
| StudentHoursReport | ✅ | ✅ | ✅ | ❌ | ❌ |
| ImpostazioniScuola | ✅ | ✅ | ✅ | ❌ | ❌ |
| News ed eventi (NewsPostResource) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Contenuti sito (ContenutiSito) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Insegnanti sito pubblico (TeacherProfileResource) — da non confondere con "Docenti" (HR) | ✅ | ✅ | ✅ | ❌ | ❌ |
| StudentUnsubscribeResource | ✅ | ✅ | ✅ | ❌ | ❌ |
| **UserResource (Users)** | ✅ tutti | ✅ no Superadmin* | ✅ no Superadmin* | ❌ | ❌ |
| Stampa contratto / PDF | ✅ | ✅ | ✅ | ❌ | propri |
| Materiali / Compiti / Quiz | ✅ | ✅ | ✅ | ✅ | leggi |
| Calendario lezioni | ✅ | ✅ | ✅ | proprio | proprio |

**\* Users — comportamento per Amministrazione/Segreteria:**
- I record con ruolo Superadmin sono filtrati dall'elenco (via `getEloquentQuery`)
- Il bottone Modifica/Elimina restituisce 403 se il record è Superadmin (via `canEdit`/`canDelete`)
- Il Select dei ruoli in fase di creazione/modifica nasconde l'opzione Superadmin (via `Select::make('roles')->options(...)`)
- Accesso diretto via URL `/users/{id}/edit` di un Superadmin → 404 (record fuori query)

---

## 3. Incongruenze documentate (da verificare/risolvere)

### 3.1 Ruoli legacy lowercase
Il codice cerca anche `super_admin`, `admin`, `docente` (lowercase) in vari `hasAnyRole()`. Questi ruoli **non sono seedati** dal `RoleSeeder`. Sono lì per retrocompatibilità con un seeder vecchio.
**Azione:** decidere se rimuoverli definitivamente (cercare con grep `'super_admin'` e `'admin'|` nel codice).

### 3.2 Tre seeder ruoli paralleli
Esistono `RoleSeeder.php`, `ShieldSeeder.php`, e seeder Filament Shield auto-generati.
**Azione:** consolidare in un unico seeder canonico.

### 3.3 Permessi `area_*` non usati
Lo `RoleSeeder` definisce permessi `area_admin`, `area_segreteria`, `area_docente` che non sono utilizzati in nessun `can(...)` nel codice.
**Azione:** rimuoverli o renderli effettivi.

### 3.4 Shield permessi mai seedati
Filament Shield genera permessi automatici per ogni Resource (es. `view_any_student`, `create_student`...) ma il loro seeding non è stato eseguito.
**Azione:** lanciare `php artisan shield:generate --all` e `php artisan shield:seed`.

### 3.5 PaymentsReport / AnomalyReport / StudentHoursReport accessi
Questi report hanno `canAccess()` che limita a superadmin. Da decidere se Amministrazione deve vederli.
**Azione:** allineare con la matrice attesa di sopra (decisione business).

### 3.6 EnrollmentResource deprecata
`canAccess()` ritorna `false` permanentemente. Le pages `Pages/CreateEnrollment.php` e `Pages/EditEnrollment.php` esistono ma sono inaccessibili.
**Azione:** valutare rimozione completa dopo verifica che Shield non abbia permessi orfani `page_EnrollmentResource`.

---

## 4. Override Gate::before per superadmin

In `app/Providers/AuthServiceProvider.php` (o equivalente) c'è un Gate::before che concede TUTTO a chi ha ruolo `superadmin`. Questo è il "master key" — usare con attenzione.

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('superadmin') ? true : null;
});
```

---

## 5. Come creare un nuovo utente staff

1. **Admin → Utenti → Crea Utente**.
2. Inserisci nome, email.
3. Assegna almeno un ruolo (es. `Segreteria`).
4. Salva. L'utente riceverà email automatica con link per impostare la password (se template welcome configurato).

> **Suggerimento:** non scambiare password in chiaro. Lascia che ognuno imposti la propria al primo accesso.

---

## 6. Come modificare il ruolo di un utente

1. **Admin → Utenti → seleziona utente → Modifica**.
2. Sezione "Ruoli" — togliere/aggiungere chip.
3. Salva.

> **Attenzione:** rimuovendo il ruolo `superadmin` da te stesso ti tagli fuori dal pannello superadmin. Mantieni almeno **due** superadmin attivi.

---

## 7. Audit periodico (raccomandato)

Mensile:
- Lista utenti con ruolo `superadmin`: `User::role('superadmin')->get()`. Devono essere 2-3 al massimo.
- Lista utenti inattivi (ultimo login > 90 giorni): valutare disattivazione.

Semestrale:
- Verifica matrice permessi vs uso reale.
- Revisione log accessi anomali (in `activity_log` se attivo).
- Rotazione password sensibili (admin / amministrazione).
