<?php

namespace App\Filament\Actions;

use App\Support\HousekeepingReportGate;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class PrintDailyReportAction
{
    /**
     * Tombol Generate Laporan. Scope membatasi bagian yang dicetak:
     * "maintenance" atau "housekeeping".
     */
    public static function make(string $scope, string $routeName = 'daily-report.pdf'): Action
    {
        return Action::make('printDailyReport_'.$scope)
            ->label('Generate Laporan')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->form([
                DatePicker::make('date')
                    ->label('Tanggal Laporan')
                    ->required()
                    ->default(now()),
            ])
            ->action(function (array $data) use ($scope, $routeName) {
                if ($scope === 'housekeeping') {
                    $inspection = HousekeepingReportGate::inspect($data['date']);

                    if (! $inspection['allowed']) {
                        Notification::make()
                            ->title('Laporan belum bisa digenerate')
                            ->body(HousekeepingReportGate::message($inspection))
                            ->danger()
                            ->persistent()
                            ->send();

                        return null;
                    }
                }

                return redirect()->to(route($routeName, [
                    'date' => $data['date'],
                    'scope' => $scope,
                ]));
            });
    }
}
