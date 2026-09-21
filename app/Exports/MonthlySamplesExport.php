<?php

namespace App\Exports;

use App\Models\Sample;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MonthlySamplesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $start;
    protected $end;
    protected $clientIds;

    public function __construct($start, $end, $clientIds = [])
    {
        $this->start = $start;
        $this->end = $end;
        $this->clientIds = $clientIds;
    }

    public function collection()
    {
        $query = Sample::leftJoin('tasks', 'tasks.id', '=', 'samples.task_id')
            ->select(
                DB::raw("DATE_FORMAT(samples.created_at, '%Y-%m') as month_year"),
                DB::raw("COUNT(samples.id) as total_samples")
            )
            ->whereBetween('samples.created_at', [$this->start->startOfDay(), $this->end->endOfDay()])
            ->where(function($query) {
                $query->where('samples.confirmed_by_client', '!=', 'LOST')
                      ->orWhereNull('samples.confirmed_by_client');
            });

        if (!empty($this->clientIds)) {
            $query->whereIn('tasks.billing_client', $this->clientIds);
        }

        return $query->groupBy('month_year')
                     ->orderBy('month_year', 'asc')
                     ->get();
    }

    public function headings(): array
    {
        return [
            'Month / Year',
            'Transferred Samples Count'
        ];
    }

    public function map($row): array
    {
        return [
            $row->month_year,
            $row->total_samples
        ];
    }
}
