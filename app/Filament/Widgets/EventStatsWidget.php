<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Waitlist;
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

        // Revenue tracking
        $totalRevenue = Registration::whereIn('payment_status', ['paid', 'partial'])
            ->sum('paid_amount');
        $recentRevenue = Registration::where('created_at', '>=', now()->subDays(7))
            ->whereIn('payment_status', ['paid', 'partial'])
            ->sum('paid_amount');

        return [
            Stat::make('Active Events', $activeEvents)
                ->description('Currently accepting registrations')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary')
                ->chart($this->getEventTrend()),

            Stat::make('Total Revenue', 'CHF ' . number_format($totalRevenue, 2))
                ->description('All-time earnings')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart($this->getRevenueTrend()),

            Stat::make('Total Registrations', $totalRegistrations)
                ->description('Confirmed attendees')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart($this->getRegistrationTrend()),

            Stat::make('Last 7 Days Revenue', 'CHF ' . number_format($recentRevenue, 2))
                ->description($recentRegistrations . ' new registrations')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('primary'),

            Stat::make('Pending Payments', $pendingPayments)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color($pendingPayments > 0 ? 'warning' : 'gray'),

            Stat::make('Waitlist', Waitlist::where('status', 'waiting')->count())
                ->description('People waiting for spots')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
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

    protected function getRevenueTrend(): array
    {
        // Simple 7-day revenue trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $amount = Registration::where('created_at', '>=', $date)
                ->where('created_at', '<', $date->copy()->addDay())
                ->whereIn('payment_status', ['paid', 'partial'])
                ->sum('paid_amount');
            $trend[] = (float) $amount;
        }
        return $trend;
    }
}
