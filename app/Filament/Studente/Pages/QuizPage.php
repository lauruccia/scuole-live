<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class QuizPage extends Page
{
    use HasStudentScope;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Test di livello';
    protected static ?string $title           = 'Test di livello';
    protected static string  $view            = 'filament.studente.pages.quiz-page';
    protected static ?int    $navigationSort  = 5;

    // Fasi: 'select' | 'quiz' | 'result'
    public string $phase = 'select';

    public string $selectedLanguage = '';

    /** @var array Domande del quiz corrente [['id', 'question_text', 'options', 'cefr_level']] */
    public array $questions = [];

    /** @var array Risposte date dallo studente [question_id => given_index] */
    public array $answers = [];

    /** @var array Risultato del quiz */
    public array $result = [];

    /** @var array Tentativi precedenti */
    public array $pastAttempts = [];

    public function mount(): void
    {
        $this->loadPastAttempts();
    }

    public function startQuiz(): void
    {
        if (empty($this->selectedLanguage)) {
            Notification::make()->title('Seleziona una lingua')->warning()->send();
            return;
        }

        $questions = QuizQuestion::forQuiz($this->selectedLanguage, 3);

        if ($questions->count() < 6) {
            Notification::make()
                ->title('Quiz non disponibile')
                ->body('Non ci sono abbastanza domande per questa lingua. Contatta la segreteria.')
                ->warning()
                ->send();
            return;
        }

        $this->questions = $questions->map(fn (QuizQuestion $q) => [
            'id'           => $q->id,
            'question_text' => $q->question_text,
            'options'      => $q->options,
            'cefr_level'   => $q->cefr_level,
        ])->values()->toArray();

        $this->answers = [];
        $this->phase   = 'quiz';
    }

    public function selectAnswer(int $questionId, int $index): void
    {
        $this->answers[$questionId] = $index;
    }

    public function submitQuiz(): void
    {
        if (count($this->answers) < count($this->questions)) {
            Notification::make()
                ->title('Rispondi a tutte le domande')
                ->warning()
                ->send();
            return;
        }

        // Calcola il livello
        $answersArray = array_map(
            fn ($questionId, $givenIndex) => ['question_id' => $questionId, 'given_index' => $givenIndex],
            array_keys($this->answers),
            array_values($this->answers)
        );

        $level = QuizQuestion::calculateLevel($answersArray);

        // Calcola punteggio totale
        $questionIds = array_column($this->questions, 'id');
        $questionsDb = QuizQuestion::whereIn('id', $questionIds)->get()->keyBy('id');
        $score       = 0;

        foreach ($this->answers as $qId => $given) {
            $q = $questionsDb[$qId] ?? null;
            if ($q && (int) $given === $q->correct_index) {
                $score++;
            }
        }

        // Salva il tentativo
        $attempt = QuizAttempt::create([
            'language'        => $this->selectedLanguage,
            'user_id'         => auth()->id(),
            'answers'         => $answersArray,
            'score'           => $score,
            'total_questions' => count($this->questions),
            'result_level'    => $level,
            'ip_address'      => request()->ip(),
        ]);

        $this->result = [
            'level'           => $level,
            'score'           => $score,
            'total'           => count($this->questions),
            'percent'         => (int) round($score / count($this->questions) * 100),
            'language'        => $this->selectedLanguage,
        ];

        $this->loadPastAttempts();
        $this->phase = 'result';
    }

    public function resetQuiz(): void
    {
        $this->phase           = 'select';
        $this->selectedLanguage = '';
        $this->questions       = [];
        $this->answers         = [];
        $this->result          = [];
    }

    private function loadPastAttempts(): void
    {
        $this->pastAttempts = QuizAttempt::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (QuizAttempt $a) => [
                'language'     => $a->language,
                'result_level' => $a->result_level,
                'score'        => $a->score,
                'total'        => $a->total_questions,
                'date'         => $a->created_at->format('d/m/Y'),
            ])
            ->toArray();
    }
}
