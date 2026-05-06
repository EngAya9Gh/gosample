<?php

namespace App\Exports;

use App\Models\Driver;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlyPerformanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        $start = Carbon::parse($this->month)->startOfMonth();
        $end = Carbon::parse($this->month)->isCurrentMonth() ? now() : Carbon::parse($this->month)->endOfMonth();

        // Calculate Expected Days (Excluding Fridays)
        $expectedDays = 0;
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if ($tempDate->dayOfWeek !== Carbon::FRIDAY) {
                $expectedDays++;
            }
            $tempDate->addDay();
        }

        return Driver::with(['attendances' => function($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start->toDateString() . ' 00:00:00', $end->toDateTimeString()]);
        }])->get()->map(function($driver) use ($expectedDays) {
            $attendances = $driver->attendances;
            
            $driver->days_present = $attendances->unique(function ($item) {
                return Carbon::parse($item->created_at)->toDateString();
            })->count();
            
            $driver->days_absent = max(0, $expectedDays - $driver->days_present);
            $driver->days_late = $attendances->where('is_late', true)->count();
            $driver->total_delay = $attendances->sum('delay_minutes');
            $driver->balance = round(($attendances->sum('overtime_minutes') - $attendances->sum('early_leave_minutes')) / 60, 1);
            
            $pScore = $driver->punctuality_score;
            $cScore = $driver->shift_completion_score;
            $driver->final_score = round(($pScore * 0.6) + ($cScore * 0.4));
            
            return $driver;
        });
    }

    public function headings(): array
    {
        return [
            'Driver ID',
            'Name',
            'Days Present',
            'Days Absent',
            'Days Late',
            'Total Delay (Min)',
            'Hrs Balance',
            'Performance Score (%)'
        ];
    }

    public function map($driver): array
    {
        return [
            $driver->id,
            $driver->name,
            $driver->days_present,
            $driver->days_absent,
            $driver->days_late,
            $driver->total_delay,
            ($driver->balance >= 0 ? '+' : '') . $driver->balance . 'h',
            $driver->final_score . '%'
        ];
    }
}
