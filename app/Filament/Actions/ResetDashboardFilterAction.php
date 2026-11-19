<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class ResetDashboardFilterAction
{
    /**
     * Reset filter global dashboard (periode, fungsi, teknisi) ke default.
     */
    public static function make(): Action
    {
        return Action::make('resetDashboardFilters')
            ->label('Reset Filter')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Reset Filter')
            ->modalDescription('Kembalikan filter global ke default (semua waktu, semua fungsi, semua teknisi).')
            ->modalSubmitActionLabel('Reset')
            ->action(function (Component $livewire): void {
                if (! property_exists($livewire, 'filters')) {
                    return;
                }

                $livewire->filters = [];

                $livewire->dispatch('dashboard-filters-updated', filters: []);

                Notification::make()
                    ->title('Filter direset')
                    ->body('Filter global dikembalikan ke default.')
                    ->success()
                    ->send();
            });
    }
}
