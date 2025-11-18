<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentRegistrationsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Registrations')
            ->query(
                Registration::query()
                    ->with('event')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),

                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Draft',
                        'pending_payment' => 'Awaiting Payment',
                        'payment_processing' => 'Processing',
                        'confirmed' => 'Confirmed',
                        'abandoned' => 'Abandoned',
                        'payment_failed' => 'Payment Failed',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->color(fn ($state) => match($state) {
                        'confirmed' => 'success',
                        'pending_payment' => 'warning',
                        'payment_processing' => 'info',
                        'abandoned' => 'gray',
                        'payment_failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'confirmed' => 'heroicon-o-check-circle',
                        'pending_payment' => 'heroicon-o-credit-card',
                        'payment_processing' => 'heroicon-o-arrow-path',
                        'payment_failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-document',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->url(fn (Registration $record): string => route('filament.admin.resources.registrations.view', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
