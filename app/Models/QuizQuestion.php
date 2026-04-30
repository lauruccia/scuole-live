<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    protected $fillable = [
        'language',
        'question_text',
        'options',
        'correct_index',
        'cefr_level',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options'        => 'array',
        'correct_index'  => 'integer',
        'sort_order'     => 'integer',
        'is_active'      => 'boolean',
    ];

    public const CEFR_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Restituisce domande randomizzate per un quiz:
     * N domande per ogni livello CEFR, per la lingua richiesta.
     *
     * @param  string  $language  Lingua (es. 'Inglese')
     * @param  int     $perLevel  Quante domande per livello (default 3 → 18 tot)
     */
    public static function forQuiz(string $language, int $perLevel = 3): \Illuminate\Support\Collection
    {
        return collect(self::CEFR_LEVELS)->flatMap(function (string $level) use ($language, $perLevel) {
            return static::where('language', $language)
                ->where('cefr_level', $level)
                ->where('is_active', true)
                ->inRandomOrder()
                ->limit($perLevel)
                ->get();
        });
    }

    /**
     * Calcola il livello CEFR in base al punteggio percentuale.
     * Logica: il livello è quello più alto in cui si risponde correttamente
     * ad almeno il 60% delle domande.
     *
     * @param  array  $answers  [['question_id' => 1, 'given_index' => 2], ...]
     */
    public static function calculateLevel(array $answers): string
    {
        $byLevel = [];

        $questionIds = array_column($answers, 'question_id');
        $questions   = static::whereIn('id', $questionIds)->get()->keyBy('id');

        foreach ($answers as $answer) {
            $q = $questions[$answer['question_id']] ?? null;
            if (! $q) continue;

            $level = $q->cefr_level;
            $byLevel[$level] ??= ['correct' => 0, 'total' => 0];
            $byLevel[$level]['total']++;

            if ((int) $answer['given_index'] === $q->correct_index) {
                $byLevel[$level]['correct']++;
            }
        }

        $achieved = 'A1';
        foreach (self::CEFR_LEVELS as $level) {
            $data = $byLevel[$level] ?? null;
            if (! $data || $data['total'] === 0) continue;

            $pct = $data['correct'] / $data['total'];
            if ($pct >= 0.60) {
                $achieved = $level;
            }
        }

        return $achieved;
    }
}
