<?php

namespace App\Filament\Resources\QuizQuestionResource\Pages;

use App\Filament\Resources\QuizQuestionResource;
use App\Models\QuizQuestion;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;

class ListQuizQuestions extends ListRecords
{
    protected static string $resource = QuizQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuova domanda'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getSubheading(): string|HtmlString|null
    {
        // Riepilogo domande per lingua e livello
        $counts = QuizQuestion::where('is_active', true)
            ->selectRaw('language, cefr_level, count(*) as cnt')
            ->groupBy('language', 'cefr_level')
            ->orderBy('language')
            ->orderBy('cefr_level')
            ->get();

        if ($counts->isEmpty()) {
            return new HtmlString('<span class="text-sm text-gray-500">Nessuna domanda ancora inserita. Carica almeno 3 domande per livello CEFR per ogni lingua per abilitare il quiz.</span>');
        }

        $byLang = $counts->groupBy('language');
        $parts = [];
        foreach ($byLang as $lang => $rows) {
            $levels = $rows->map(fn ($r) => $r->cefr_level . ':' . $r->cnt)->implode(' ');
            $parts[] = "<strong>{$lang}</strong>: {$levels}";
        }

        return new HtmlString(
            '<span class="text-sm text-gray-600">Domande attive — ' . implode(' | ', $parts) . '</span>'
        );
    }
}
