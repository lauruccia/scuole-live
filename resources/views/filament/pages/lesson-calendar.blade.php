<x-filament-panels::page>
    <style>
        .lesson-event { border-radius: 10px; border-width: 1px; }
        .lesson-event .fc-event-title { font-weight: 600; }

        /* dot badge */
        .lesson-event { position: relative; padding-left: 18px !important; }
        .lesson-event::before {
            content: "";
            width: 8px; height: 8px;
            border-radius: 999px;
            position: absolute;
            left: 6px;
            top: 8px;
        }

        /* Programmata */
        .lesson-programmata { background: #EFF6FF !important; border-color: #93C5FD !important; color: #0F172A !important; }
        .lesson-programmata::before { background: #3B82F6; }

        /* Completata */
        .lesson-completata { background: #ECFDF5 !important; border-color: #6EE7B7 !important; color: #0F172A !important; }
        .lesson-completata::before { background: #10B981; }

        /* Annullata (da recuperare) */
        .lesson-annullata-recover { background: #FFFBEB !important; border-color: #FDE68A !important; color: #0F172A !important; }
        .lesson-annullata-recover::before { background: #F59E0B; }

        /* Annullata */
        .lesson-annullata { background: #FEF2F2 !important; border-color: #FCA5A5 !important; color: #7F1D1D !important; text-decoration: line-through; }
        .lesson-annullata::before { background: #EF4444; }

        /* ✅ click sempre */
        .fc .fc-event,
        .fc .fc-event-main,
        .fc .fc-event-main-frame,
        .fc .fc-event-title-container,
        .fc .fc-daygrid-event {
            pointer-events: auto !important;
            cursor: pointer !important;
        }

        .fc .fc-daygrid-day-frame,
        .fc .fc-daygrid-day-events {
            pointer-events: auto;
        }

        .lesson-tooltip{
            position: fixed;
            z-index: 9999;
            white-space: pre-line;
            font-size: 12px;
            line-height: 1.2;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid rgba(15,23,42,.15);
            background: rgba(255,255,255,.95);
            box-shadow: 0 10px 18px rgba(2,6,23,.10);
            color: #0f172a;
            pointer-events: none;
        }

        /* box evento un filo più compatto */
        .fc .fc-daygrid-event {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        /* ✅ rende più leggibile la parte ora */
        .fc .fc-event-time {
            font-weight: 700;
            margin-right: 6px;
        }
    </style>

    <div class="space-y-4">
        @livewire(\App\Filament\Widgets\LessonCalendarWidget::class)
    </div>
</x-filament-panels::page>
