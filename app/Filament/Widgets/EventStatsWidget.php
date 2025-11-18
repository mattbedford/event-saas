<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeEvents = Event::where('is_active', true)->count();
        $totalRegistrations = Registration::where('registration_status', 'confirmed')->count();
        $pendingPayments = Registration::whereIn('registration_status', ['pending_payment', 'payment_processing'])->count();
        $recentRegistrations = Registration::where('created_at', '>=', now()->subDays(7))
            ->where('registration_status', 'confirmed')
            ->count();

        return [
            Stat::make('Active Events', $activeEvents)
                ->description('Currently accepting registrations')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary')
                ->chart($this->getEventTrend()),

            Stat::make('Total Registrations', $totalRegistrations)
                ->description('Confirmed attendees')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart($this->getRegistrationTrend()),

            Stat::make('Pending Payments', $pendingPayments)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color($pendingPayments > 0 ? 'warning' : 'gray'),

            Stat::make('Last 7 Days', $recentRegistrations)
                ->description('New registrations this week')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('primary'),
        ];
    }

    protected function getEventTrend(): array
    {
        // Simple 7-day trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $trend[] = Event::where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->addDay())
                ->count();
        }
        return $trend;
    }

    protected function getRegistrationTrend(): array
    {
        // Simple 7-day trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $trend[] = Registration::where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->addDay())
                ->where('registration_status', 'confirmed')
                ->count();
        }
        return $trend;
    }
}
