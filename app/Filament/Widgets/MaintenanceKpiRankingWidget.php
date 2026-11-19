<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\WorkOrder;
use App\Support\Kpi\KpiPeriod;
use App\Support\Kpi\KpiRegistry;
use App\Support\Kpi\KpiResult;
use App\Support\Kpi\KpiService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Peringkat KPI maintenance di Dashboard Maintenance.
 * Mengikuti filter global (periode, fungsi, teknisi).
 */
class MaintenanceKpiRankingWidget extends BaseWidget
{
    use FiltersByDate;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Peringkat KPI Maintenance';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getDescription(): ?string
    {
        return 'Penilaian per teknisi/koordinator pada '.$this->kpiPeriod()->label.'.';
    }

    protected function kpiPeriod(): KpiPeriod
    {
        $from = $this->dateFilterFrom();
        $to = $this->dateFilterTo();

        if ($from && $to) {
            return new KpiPeriod($from, $to, 'Periode terpilih');
        }

        if ($this->dateFilterRange() === 'all') {
            $earliest = WorkOrder::query()->min('start_at');

            return new KpiPeriod(
                $earliest ? Carbon::parse($earliest)->startOfDay() : now()->startOfYear(),
                now()->endOfDay(),
                'Semua Waktu',
            );
        }

        return KpiPeriod::fromPreset('this_month');
    }

    /**
     * @return Collection<int, KpiResult>
     */
    protected function results(): Collection
    {
        return app(KpiService::class)->results(
            KpiRegistry::SOURCE_MAINTENANCE,
            $this->kpiPeriod(),
            $this->fungsiFilter(),
            $this->userFilterId(),
        );
    }

    public function table(Table $table): Table
    {
        $results = $this->results();

        return $table
            ->records(fn (): array => $results
                ->map(fn (KpiResult $result): array => [
                    'id' => $result->objectId,
                    'objectName' => $result->objectName,
                    'fungsi' => $result->context['fungsi'] ?? null,
                    'total_wo' => $result->context['total_wo'] ?? 0,
                    'completed_wo' => $result->context['completed_wo'] ?? 0,
                    'open_wo' => $result->context['open_wo'] ?? 0,
                    'expired_wo' => $result->context['expired_wo'] ?? 0,
                    'completion' => $result->metrics['wo_completion_rate']['display'] ?? '-',
                    'on_time' => $result->metrics['wo_on_time_rate']['display'] ?? '-',
                    'checklist' => $result->metrics['checklist_compliance']['display'] ?? '-',
                    'score' => $result->score,
                    'grade' => $result->grade(),
                    'grade_color' => $result->gradeColor(),
                    'status' => $result->status(),
                    'status_color' => $result->statusColor(),
                ])
                ->values()
                ->all())
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('objectName')
                    ->label('Name')
                    ->weight('medium'),
                TextColumn::make('fungsi')
                    ->label('Fungsi')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('total_wo')
                    ->label('Total WO')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('completed_wo')
                    ->label('Selesai')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                TextColumn::make('open_wo')
                    ->label('Belum')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (array $record): string => ($record['open_wo'] ?? 0) > 0 ? 'warning' : 'gray'),
                TextColumn::make('expired_wo')
                    ->label('Expired')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (array $record): string => ($record['expired_wo'] ?? 0) > 0 ? 'danger' : 'gray'),
                TextColumn::make('completion')
                    ->label('Completion')
                    ->alignCenter(),
                TextColumn::make('on_time')
                    ->label('On-Time')
                    ->alignCenter(),
                TextColumn::make('checklist')
                    ->label('Checklist')
                    ->alignCenter(),
                TextColumn::make('score')
                    ->label('Skor')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (array $record): string => $record['grade_color']),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (array $record): string => $record['grade_color']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (array $record): string => $record['status_color']),
            ])
            ->paginated([5, 10, 25])
            ->defaultSort('score', 'desc')
            ->emptyStateHeading('Belum ada data KPI')
            ->emptyStateDescription('Belum ada WO yang ditugaskan pada periode/fungsi terpilih.');
    }
}
