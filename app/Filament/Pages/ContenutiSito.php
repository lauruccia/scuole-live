<?php

namespace App\Filament\Pages;

use App\Models\PageContent;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Sito web → Contenuti sito
 *
 * Editor dei testi/immagini/FAQ delle pagine pubbliche (mini-CMS).
 * Campi e default sono definiti in config/site_contents.php; il DB
 * (page_contents) contiene solo le personalizzazioni. Svuotare un campo
 * e salvare = ripristinare il testo originale.
 *
 * Accesso: superadmin + admin + Amministrazione + Segreteria
 * (stessa platea del modulo News — vedi PERMESSI.md).
 */
class ContenutiSito extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Contenuti sito';
    protected static ?string $navigationGroup = 'Sito web';
    protected static ?string $title           = 'Contenuti sito';
    protected static ?int    $navigationSort  = 20;
    protected static string  $view            = 'filament.pages.contenuti-sito';

    /** Pagina attualmente selezionata (slug della config site_contents). */
    public string $pagina = 'home';

    public array $data = [];

    /* ─── Accesso ────────────────────────────────────────────────────────── */

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) return false;

        return $u->hasAnyRole(['superadmin', 'super_admin', 'admin', 'Amministrazione', 'Segreteria']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /* ─── Ciclo di vita ──────────────────────────────────────────────────── */

    public function mount(): void
    {
        $this->fillFormForPage();
    }

    /** Cambiando pagina dal menu a tendina, ricarica i campi. */
    public function updatedPagina(): void
    {
        $this->fillFormForPage();
    }

    /** Opzioni per il selettore pagina nella view. */
    public function getPagineProperty(): array
    {
        return collect(config('site_contents', []))
            ->map(fn ($page) => $page['label'] ?? '?')
            ->all();
    }

    /* ─── Form ───────────────────────────────────────────────────────────── */

    protected function fillFormForPage(): void
    {
        $values = [];

        foreach ($this->fieldsForPage() as $key => $def) {
            $custom  = PageContent::allFor($this->pagina)[$key] ?? null;
            $default = $def['default'] ?? null;

            $values[$key] = match ($def['type']) {
                'faq'   => $this->faqState($custom, $default),
                'image' => ($custom !== null && $custom !== '') ? $custom : null,
                default => ($custom !== null && $custom !== '') ? $custom : $default,
            };
        }

        $this->form->fill($values);
    }

    /** Stato del repeater FAQ: JSON personalizzato se presente, altrimenti default. */
    protected function faqState(?string $custom, mixed $default): array
    {
        if (is_string($custom) && $custom !== '') {
            $decoded = json_decode($custom, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return is_array($default) ? $default : [];
    }

    /** Mappa piatta key => definizione campo della pagina selezionata. */
    protected function fieldsForPage(): array
    {
        $fields = [];
        foreach (config("site_contents.{$this->pagina}.sections", []) as $section) {
            foreach (($section['fields'] ?? []) as $key => $def) {
                $fields[$key] = $def;
            }
        }

        return $fields;
    }

    public function form(Form $form): Form
    {
        $schema = [];

        foreach (config("site_contents.{$this->pagina}.sections", []) as $sectionKey => $section) {
            $fields = [];

            foreach (($section['fields'] ?? []) as $key => $def) {
                $fields[] = $this->makeField($key, $def);
            }

            $schema[] = Section::make($section['label'] ?? $sectionKey)
                ->collapsible()
                ->collapsed($sectionKey !== 'hero' && $sectionKey !== 'contenuto')
                ->schema($fields);
        }

        return $form->statePath('data')->schema($schema);
    }

    protected function makeField(string $key, array $def): \Filament\Forms\Components\Component
    {
        $label = $def['label'] ?? $key;

        return match ($def['type']) {
            'textarea' => Textarea::make($key)
                ->label($label)
                ->rows(3),

            'html' => Textarea::make($key)
                ->label($label)
                ->rows(3)
                ->helperText('Puoi usare HTML semplice: <strong>, <em>, <br>, <a href="…">.'),

            'richtext' => RichEditor::make($key)
                ->label($label)
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo']),

            'image' => FileUpload::make($key)
                ->label($label)
                ->image()
                ->disk('public')
                ->directory('pages')
                ->maxSize(4096)
                ->helperText('Se lasci vuoto viene usata l\'immagine predefinita.'),

            'lines' => Textarea::make($key)
                ->label($label)
                ->rows(6)
                ->helperText('Una voce per riga.'),

            'faq' => Repeater::make($key)
                ->label($label)
                ->schema([
                    TextInput::make('q')->label('Domanda')->required(),
                    RichEditor::make('a')->label('Risposta')
                        ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'undo', 'redo']),
                ])
                ->addActionLabel('Aggiungi domanda')
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['q'] ?? null),

            default => TextInput::make($key)->label($label),
        };
    }

    /* ─── Salvataggio ────────────────────────────────────────────────────── */

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salva contenuti')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->fieldsForPage() as $key => $def) {
            $value   = $state[$key] ?? null;
            $default = $def['default'] ?? null;

            if ($def['type'] === 'faq') {
                $value = is_array($value) ? array_values($value) : [];
                // Uguale al default (o vuoto) → nessuna personalizzazione.
                if ($value === [] || $value == $default) {
                    PageContent::reset($this->pagina, $key);
                } else {
                    PageContent::put($this->pagina, $key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                continue;
            }

            if ($def['type'] === 'image') {
                $value = is_array($value) ? (array_values($value)[0] ?? null) : $value;
                if ($value === null || $value === '') {
                    PageContent::reset($this->pagina, $key);
                } else {
                    PageContent::put($this->pagina, $key, (string) $value);
                }
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            // Campo vuoto o identico al default → togli la personalizzazione,
            // così la pagina segue sempre il testo predefinito (ripristino).
            if ($value === null || $value === '' || $value === $default) {
                PageContent::reset($this->pagina, $key);
            } else {
                PageContent::put($this->pagina, $key, (string) $value);
            }
        }

        PageContent::forgetPage($this->pagina);

        Notification::make()
            ->title('Contenuti salvati')
            ->body('Le modifiche saranno visibili sul sito entro pochi minuti.')
            ->success()
            ->send();
    }
}
