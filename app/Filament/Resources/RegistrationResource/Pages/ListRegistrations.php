<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Registration;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Registrations')
                ->badge(Registration::count()),

            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('registration_status', 'confirmed'))
                ->badge(Registration::where('registration_status', 'confirmed')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'needs_attention' => Tab::make('Needs Attention')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('registration_status', ['payment_failed', 'payment_processing']))
                ->badge(Registration::whereIn('registration_status', ['payment_failed', 'payment_processing'])->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle'),

            'drafts' => Tab::make('Drafts & Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('registration_status', ['draft', 'pending_payment']))
                ->badge(Registration::whereIn('registration_status', ['draft', 'pending_payment'])->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'abandoned' => Tab::make('Abandoned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('registration_status', 'abandoned'))
                ->badge(Registration::where('registration_status', 'abandoned')->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
