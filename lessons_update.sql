-- ============================================================
-- UPDATE lezioni: vecchio DB → nuovo DB
-- Generato il 2026-05-08
-- Studenti: LUCA MARGHERITA (1492), ALEANDRI LIVIO (1866), CRISTINA GIACOMO (2337)
-- ============================================================

-- 35 lezioni modificate


-- ── LUCA MARGHERITA (student_id=1492) ──────────────────────

-- Lezione ID 10806 | 2026-05-09 10:00:00 | Differenze:
--   starts_at: NEW=[2026-03-28 10:00:00]  →  OLD=[2026-05-09 10:00:00]
--   ends_at: NEW=[2026-03-28 11:00:00]  →  OLD=[2026-05-09 11:00:00]
--   notes: NEW=[FULL CON PICCOLO EMMA]  →  OLD=[NULL]
-- ⚠️ aggiornata PRIMA di 10804 per liberare lo slot 2026-03-28
UPDATE lessons SET
    starts_at = '2026-05-09 10:00:00',
    ends_at = '2026-05-09 11:00:00',
    notes = NULL,
    updated_at = '2026-05-08 09:13:05'
WHERE id = 10806;

-- Lezione ID 10804 | 2026-03-28 10:00:00 | Differenze:
--   starts_at: NEW=[2026-03-14 10:00:00]  →  OLD=[2026-03-28 10:00:00]
--   ends_at: NEW=[2026-03-14 11:00:00]  →  OLD=[2026-03-28 11:00:00]
--   notes: NEW=[NULL]  →  OLD=[full con Piccolo Emma]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:57:20]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2026-03-28 10:00:00',
    ends_at = '2026-03-28 11:00:00',
    notes = 'full con Piccolo Emma',
    completed_at = '2026-05-08 08:57:20',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:57:20'
WHERE id = 10804;

-- Lezione ID 1465 | 2025-09-20 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:04:44]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:04:44',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:04:44'
WHERE id = 1465;

-- Lezione ID 1466 | 2025-09-27 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:04:39]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:04:39',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:04:39'
WHERE id = 1466;

-- Lezione ID 1467 | 2025-10-07 10:00:00 | Differenze:
--   starts_at: NEW=[2025-10-04 10:00:00]  →  OLD=[2025-10-07 10:00:00]
--   ends_at: NEW=[2025-10-04 11:00:00]  →  OLD=[2025-10-07 11:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:08:18]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2025-10-07 10:00:00',
    ends_at = '2025-10-07 11:00:00',
    completed_at = '2026-05-08 09:08:18',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:08:18'
WHERE id = 1467;

-- Lezione ID 1468 | 2025-10-09 10:00:00 | Differenze:
--   starts_at: NEW=[2025-10-11 10:00:00]  →  OLD=[2025-10-09 10:00:00]
--   ends_at: NEW=[2025-10-11 11:00:00]  →  OLD=[2025-10-09 11:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:09:42]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2025-10-09 10:00:00',
    ends_at = '2025-10-09 11:00:00',
    completed_at = '2026-05-08 09:09:42',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:09:42'
WHERE id = 1468;

-- Lezione ID 1469 | 2025-10-18 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:04:01]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:04:01',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:04:01'
WHERE id = 1469;

-- Lezione ID 1470 | 2025-10-25 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:03:28]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:03:28',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:03:28'
WHERE id = 1470;

-- Lezione ID 1472 | 2025-11-08 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:03:08]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:03:08',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:03:08'
WHERE id = 1472;

-- Lezione ID 1473 | 2025-11-15 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:02:44]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:02:44',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:02:44'
WHERE id = 1473;

-- Lezione ID 1474 | 2025-11-26 15:00:00 | Differenze:
--   starts_at: NEW=[2025-11-22 10:00:00]  →  OLD=[2025-11-26 15:00:00]
--   ends_at: NEW=[2025-11-22 11:00:00]  →  OLD=[2025-11-26 16:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:12:29]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2025-11-26 15:00:00',
    ends_at = '2025-11-26 16:00:00',
    completed_at = '2026-05-08 09:12:29',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:12:29'
WHERE id = 1474;

-- Lezione ID 1475 | 2025-12-02 10:00:00 | Differenze:
--   starts_at: NEW=[2025-11-29 10:00:00]  →  OLD=[2025-12-02 10:00:00]
--   ends_at: NEW=[2025-11-29 11:00:00]  →  OLD=[2025-12-02 11:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:09:51]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2025-12-02 10:00:00',
    ends_at = '2025-12-02 11:00:00',
    completed_at = '2026-05-08 09:09:51',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:09:51'
WHERE id = 1475;

-- Lezione ID 1476 | 2025-12-06 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:02:11]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:02:11',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:02:11'
WHERE id = 1476;

-- Lezione ID 1477 | 2025-12-13 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:01:59]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:01:59',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:01:59'
WHERE id = 1477;

-- Lezione ID 1478 | 2025-12-20 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:01:35]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:01:35',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:01:35'
WHERE id = 1478;

-- Lezione ID 1487 | 2026-03-14 10:00:00 | Differenze:
--   starts_at: NEW=[2026-02-21 10:00:00]  →  OLD=[2026-03-14 10:00:00]
--   ends_at: NEW=[2026-02-21 11:00:00]  →  OLD=[2026-03-14 11:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:58:14]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
-- ⚠️ aggiornata PRIMA di 1480 per liberare lo slot 2026-02-21
UPDATE lessons SET
    starts_at = '2026-03-14 10:00:00',
    ends_at = '2026-03-14 11:00:00',
    completed_at = '2026-05-08 08:58:14',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:58:14'
WHERE id = 1487;

-- Lezione ID 1480 | 2026-02-21 10:00:00 | Differenze:
--   starts_at: NEW=[2026-01-03 10:00:00]  →  OLD=[2026-02-21 10:00:00]
--   ends_at: NEW=[2026-01-03 11:00:00]  →  OLD=[2026-02-21 11:00:00]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:01:06]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2026-02-21 10:00:00',
    ends_at = '2026-02-21 11:00:00',
    completed_at = '2026-05-08 09:01:06',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:01:06'
WHERE id = 1480;

-- Lezione ID 1481 | 2026-01-10 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:00:17]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 09:00:17',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:00:17'
WHERE id = 1481;

-- Lezione ID 1482 | 2026-01-17 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:59:40]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 08:59:40',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:59:40'
WHERE id = 1482;

-- Lezione ID 1483 | 2026-01-24 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:59:27]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 08:59:27',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:59:27'
WHERE id = 1483;

-- Lezione ID 1484 | 2026-01-31 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:59:14]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 08:59:14',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:59:14'
WHERE id = 1484;

-- Lezione ID 1485 | 2026-02-07 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:59:08]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 08:59:08',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:59:08'
WHERE id = 1485;

-- Lezione ID 1486 | 2026-02-14 10:00:00 | Differenze:
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:58:57]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    completed_at = '2026-05-08 08:58:57',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:58:57'
WHERE id = 1486;

-- ── ALEANDRI LIVIO (student_id=1866) ──────────────────────

-- Lezione ID 2873 | 2026-05-08 09:00:00 | Differenze:
--   teacher_id: NEW=[8]  →  OLD=[11]
--   starts_at: NEW=[2026-02-24 11:00:00]  →  OLD=[2026-05-08 09:00:00]
--   ends_at: NEW=[2026-02-24 12:00:00]  →  OLD=[2026-05-08 10:00:00]
--   cancelled_by: NEW=[13]  →  OLD=[NULL]
--   notes: NEW=[NULL]  →  OLD=[LEZIONE SVOLTA DA ALEANDRI M.C]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 08:42:03]
--   completed_by: NEW=[NULL]  →  OLD=[13]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    teacher_id = '11',
    starts_at = '2026-05-08 09:00:00',
    ends_at = '2026-05-08 10:00:00',
    cancelled_by = NULL,
    notes = 'LEZIONE SVOLTA DA ALEANDRI M.C',
    completed_at = '2026-05-08 08:42:03',
    completed_by = '13',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 08:42:03'
WHERE id = 2873;

-- ── CRISTINA GIACOMO (student_id=2337) ──────────────────────
-- Nota: nel vecchio DB contract_student_id era 164 (contract_id=92)
--       nel nuovo DB lo stesso record esiste con id=177 (contract_id=92)

-- Lezione ID 14977 | 2026-03-10 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-01 15:23:02' WHERE id = 14977;

-- Lezione ID 14978 | 2026-03-17 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-01 15:23:54' WHERE id = 14978;

-- Lezione ID 14979 | 2026-03-19 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-01 15:25:15' WHERE id = 14979;

-- Lezione ID 14980 | 2026-03-24 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-01 15:30:38' WHERE id = 14980;

-- Lezione ID 14993 | 2026-04-02 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-02 15:18:02' WHERE id = 14993;

-- Lezione ID 14994 | 2026-04-07 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-07 16:11:49' WHERE id = 14994;

-- Lezione ID 14995 | 2026-04-09 16:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-09 07:50:01' WHERE id = 14995;

-- Lezione ID 14996 | 2026-04-18 10:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-18 08:51:54' WHERE id = 14996;

-- Lezione ID 14997 | 2026-04-15 17:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-22 14:59:34' WHERE id = 14997;

-- Lezione ID 14998 | 2026-04-01 18:00:00
UPDATE lessons SET contract_student_id = 177, updated_at = '2026-04-01 17:23:27' WHERE id = 14998;

-- Lezione ID 15752 | 2026-05-08 11:00:00 | Differenze:
--   starts_at: NEW=[2026-05-12 17:00:00]  →  OLD=[2026-05-08 11:00:00]
--   ends_at: NEW=[2026-05-12 18:00:00]  →  OLD=[2026-05-08 12:00:00]
--   notes: NEW=[NULL]  →  OLD=[Simulaciòn examen]
--   completed_at: NEW=[NULL]  →  OLD=[2026-05-08 09:18:32]
--   completed_by: NEW=[NULL]  →  OLD=[12]
--   counts_as_consumed: NEW=[0]  →  OLD=[1]
UPDATE lessons SET
    starts_at = '2026-05-08 11:00:00',
    ends_at = '2026-05-08 12:00:00',
    notes = 'Simulaciòn examen',
    completed_at = '2026-05-08 09:18:32',
    completed_by = '12',
    counts_as_consumed = '1',
    updated_at = '2026-05-08 09:19:00'
WHERE id = 15752;