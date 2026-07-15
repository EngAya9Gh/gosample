<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Swap;
use App\Models\Task;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia System Calendar (/app/admin/system-calendar) — SPA rebuild of the
 * classic Admin\SystemCalendarController. Events are DERIVED from real data
 * (same source as the classic page): active Tasks grouped per day, plus Swap
 * requests. Supports Month / Week / Day views with prev/next/today navigation
 * and an .xlsx export of the visible period.
 */
class SystemCalendarController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('scheduled_task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $view = $this->view($request);
        $anchor = $this->anchor($request);
        [$start, $end] = $this->range($view, $anchor);

        $prev = match ($view) { 'month' => $anchor->copy()->subMonth(), 'week' => $anchor->copy()->subWeek(), default => $anchor->copy()->subDay() };
        $next = match ($view) { 'month' => $anchor->copy()->addMonth(), 'week' => $anchor->copy()->addWeek(), default => $anchor->copy()->addDay() };
        $label = match ($view) {
            'month' => $anchor->format('F Y'),
            'week'  => $start->format('d M') . ' – ' . $end->format('d M Y'),
            default => $anchor->format('l, d M Y'),
        };

        $payload = [
            'view'   => $view,
            'anchor' => $anchor->toDateString(),
            'label'  => $label,
            'prev'   => $prev->toDateString(),
            'next'   => $next->toDateString(),
            'today'  => Carbon::today()->toDateString(),
            'events' => $this->buildEvents($start, $end),
        ];

        // Day view: the actual tasks on that date (detailed list).
        if ($view === 'day') {
            $payload['dayItems'] = Task::whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
                ->whereDate('created_at', $anchor->toDateString())
                ->orderBy('id')
                ->get(['id', 'status', 'task_type', 'created_at'])
                ->map(fn ($t) => [
                    'id'     => $t->id,
                    'title'  => 'Task #' . $t->id,
                    'status' => $t->status,
                    'type'   => $t->task_type,
                    'time'   => optional($t->created_at)->format('H:i'),
                ]);
        }

        return Inertia::render('Tasks/SystemCalendar', $payload);
    }

    public function export(Request $request)
    {
        abort_if(Gate::denies('scheduled_task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $view = $this->view($request);
        $anchor = $this->anchor($request);
        [$start, $end] = $this->range($view, $anchor);
        $events = $this->buildEvents($start, $end);
        ksort($events);

        $rows = [];
        foreach ($events as $date => $list) {
            foreach ($list as $ev) {
                $rows[] = [$date, ucfirst($ev['type']), $ev['count'], $ev['label']];
            }
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ReportExport($rows, ['Date', 'Type', 'Count', 'Label'], 'Calendar'),
            'calendar-' . $view . '-' . $anchor->toDateString() . '.xlsx'
        );
    }

    // ---- helpers ----

    private function view(Request $request): string
    {
        return in_array($request->input('view'), ['month', 'week', 'day'], true)
            ? $request->input('view') : 'month';
    }

    private function anchor(Request $request): Carbon
    {
        try {
            return $request->input('date') ? Carbon::parse($request->input('date'))->startOfDay() : Carbon::today();
        } catch (\Throwable $e) {
            return Carbon::today();
        }
    }

    private function range(string $view, Carbon $anchor): array
    {
        return match ($view) {
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'week'  => [$anchor->copy()->startOfWeek(Carbon::SUNDAY), $anchor->copy()->endOfWeek(Carbon::SATURDAY)],
            default => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
        };
    }

    /**
     * { 'Y-m-d': [ {type,label,count} ] } for the visible range. Colors are
     * mapped by `type` on the client (TYPE_BG): task=teal, scheduled=blue,
     * swap=mauve, today=orange — matching the MTC reference palette.
     */
    private function buildEvents(Carbon $start, Carbon $end): array
    {
        $events = [];
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->endOfDay();

        // Regular (one-time) tasks — teal.
        $tasks = Task::whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
            ->where(fn ($q) => $q->where('type', '!=', 'scheduled')->orWhereNull('type'))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');
        foreach ($tasks as $d => $c) {
            $events[$d][] = ['type' => 'task', 'label' => $c . ' ' . ($c == 1 ? 'task' : 'tasks'), 'count' => (int) $c];
        }

        // Scheduled tasks — blue.
        $scheduled = Task::whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
            ->where('type', 'scheduled')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');
        foreach ($scheduled as $d => $c) {
            $events[$d][] = ['type' => 'scheduled', 'label' => $c . ' scheduled', 'count' => (int) $c];
        }

        // Swaps — mauve.
        $swaps = Swap::whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');
        foreach ($swaps as $d => $c) {
            $events[$d][] = ['type' => 'swap', 'label' => $c . ' ' . ($c == 1 ? 'swap' : 'swaps'), 'count' => (int) $c];
        }

        // "Today" marker — orange (only when today is in the visible range).
        $today = Carbon::today();
        if ($today->gte($from) && $today->lte($to)) {
            $k = $today->toDateString();
            $events[$k] = $events[$k] ?? [];
            array_unshift($events[$k], ['type' => 'today', 'label' => 'Today', 'count' => 0]);
        }

        return $events;
    }
}
