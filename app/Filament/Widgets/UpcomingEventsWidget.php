<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingEventsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Upcoming Events')
            ->query(
                Event::query()
                    ->where('event_date', '>=', now())
                    ->where('is_active', true)
                    ->orderBy('event_date', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->dateTime('M j, Y - H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->description(fn (Event $record) => $record->event_date->diffForHumans()),

                Tables\Columns\TextColumn::make('registrations_count')
                    ->counts([
                        'registrations' => fn ($query) => $query->whereIn('payment_status', ['paid', 'pending'])
                    ])
                    ->label('Registered')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-user-group'),

                Tables\Columns\TextColumn::make('capacity_info')
                    ->label('Capacity')
                    ->state(fn ($record) => $record->max_seats ?? '∞')
                    ->description(fn (Event $record) =>
                        $record->max_seats
                            ? ($record->remainingSeats() . ' available (' . $record->capacityPercentage() . '% free)')
                            : 'Unlimited'
                    )
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        !$record->hasAvailableSeats() => 'danger',
                        $record->isNearlyFull() => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('ticket_price')
                    ->money('CHF')
                    ->icon('heroicon-o-currency-dollar'),

                Tables\Columns\IconColumn::make('settings.registrations_enabled')
                    ->label('Open')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Manage')
                    ->url(fn (Event $record): string => route('filament.admin.resources.events.edit', $record))
                    ->icon('heroicon-o-cog-6-tooth'),
            ]);
    }
}
