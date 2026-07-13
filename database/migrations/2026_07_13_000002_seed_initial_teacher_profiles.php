<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed idempotente dei 3 profili insegnanti recuperati dal vecchio sito
 * WordPress (aealanguagecenter.it/teachers/*) durante il confronto vecchio
 * sito ↔ nuovo sito del 2026-07-13. Il vecchio sito non pubblicava nomi
 * propri (solo ruolo/lingua): manteniamo la stessa impostazione anonima.
 * updateOrInsert per slug: rieseguibile senza duplicare le righe.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $profiles = [
            [
                'name' => 'Insegnante di Lingua Inglese',
                'slug' => 'insegnante-di-lingua-inglese',
                'language' => 'Inglese',
                'qualifications' => 'Laurea in Lingue Straniere, laurea in Psicologia, dottorato in Lingua Inglese',
                'certifications' => 'Trinity College London (sede n° 8241), IELTS, Cambridge, TOEFL, SAT',
                'bio' => "<p>Oltre alla laurea in Lingue Straniere, la nostra insegnante di Inglese ha conseguito anche una laurea in Psicologia e un dottorato in Lingua Inglese. Ha insegnato Inglese generale e commerciale nell'università dove si è laureata.</p><p>Il suo percorso di studi le permette di unire competenza linguistica e attenzione agli aspetti psicologici dell'apprendimento: le sue lezioni sono pensate per motivare e mettere a proprio agio ogni studente, dal principiante a chi si prepara per le certificazioni internazionali.</p>",
                'order' => 1,
            ],
            [
                'name' => 'Insegnante di Francese e Tedesco',
                'slug' => 'insegnante-di-francese-e-tedesco',
                'language' => 'Francese e Tedesco',
                'qualifications' => null,
                'certifications' => 'Preparazione esami DELF/DALF (Francese), livelli A1–C2',
                'bio' => "<p>Esperienza pluriennale nell'insegnamento di Francese e Tedesco e nella preparazione di esami e certificazioni internazionali.</p><p>I corsi sono personalizzati sul livello di partenza dello studente e prevedono anche lezioni di conversazione in piccoli gruppi, sempre guidate dal docente, per consolidare fluidità e pronuncia.</p>",
                'order' => 2,
            ],
            [
                'name' => 'Insegnante di Lingua Araba',
                'slug' => 'insegnante-di-lingua-araba',
                'language' => 'Arabo',
                'qualifications' => null,
                'certifications' => null,
                'bio' => "<p>Insegnante appassionata e qualificata, con ottima padronanza della lingua italiana: riesce a rendere l'apprendimento dell'Arabo semplice e coinvolgente.</p><p>Con i più piccoli mette in campo un approccio creativo e giocoso; con gli adulti accompagna verso obiettivi di apprendimento ambiziosi, mantenendo alta la motivazione lezione dopo lezione.</p>",
                'order' => 3,
            ],
        ];

        foreach ($profiles as $profile) {
            DB::table('teacher_profiles')->updateOrInsert(
                ['slug' => $profile['slug']],
                array_merge($profile, [
                    'is_published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('teacher_profiles')->whereIn('slug', [
            'insegnante-di-lingua-inglese',
            'insegnante-di-francese-e-tedesco',
            'insegnante-di-lingua-araba',
        ])->delete();
    }
};
