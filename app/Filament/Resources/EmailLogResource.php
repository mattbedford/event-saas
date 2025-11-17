<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailLogResource\Pages;
use App\Models\EmailLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Email Management';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) EmailLog::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function canCreate(): bool
    {
        return false; // Logs are created automatically
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only form for viewing details
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Email Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('registration.full_name')
                            ->label('Recipient'),
                        Infolists\Components\TextEntry::make('registration.email')
                            ->label('Email Address')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('registration.event.name')
                            ->label('Event'),
                        Infolists\Components\TextEntry::make('emailChain.emailTemplate.name')
                            ->label('Template Used')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'warning' => 'queued',
                                'info' => 'sent',
                                'success' => ['delivered', 'opened', 'clicked'],
                                'danger' => 'failed',
                            ]),
                        Infolists\Components\TextEntry::make('brevo_message_id')
                            ->label('Brevo Message ID')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Delivery Tracking')
                    ->schema([
                        Infolists\Components\TextEntry::make('sent_at')
                            ->dateTime()
                            ->placeholder('Not sent yet'),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->dateTime()
                            ->placeholder('Not delivered yet'),
                        Infolists\Components\TextEntry::make('opened_at')
                            ->dateTime()
                            ->placeholder('Not opened yet'),
                        Infolists\Components\TextEntry::make('clicked_at')
                            ->dateTime()
                            ->placeholder('No clicks'),
                        Infolists\Components\TextEntry::make('error_message')
                            ->placeholder('No errors')
                            ->color('danger'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Brevo Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('brevo_stats')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : ($state ?? 'No statistics available'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration.full_name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('registration.event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('emailChain.emailTemplate.name')
                    ->label('Template')
                    ->limit(30)
                    ->placeholder('Manual Send'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'queued',
                        'info' => 'sent',
                        'success' => ['delivered', 'opened', 'clicked'],
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                Tables\Columns\IconColumn::make('opened')
                    ->label('Opened')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->opened_at))
                    ->sortable(),

                Tables\Columns\IconColumn::make('clicked')
                    ->label('Clicked')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->clicked_at))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->sent_at?->format('M d, Y H:i')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Queued',
                        'sent' => 'Sent',
                        'delivered' => 'Delivered',
                        'opened' => 'Opened',
                        'clicked' => 'Clicked',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->relationship('registration.event', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('opened')
                    ->label('Opened Emails')
                    ->query(fn ($query) => $query->whereNotNull('opened_at')),

                Tables\Filters\Filter::make('clicked')
                    ->label('Clicked Emails')
                    ->query(fn ($query) => $query->whereNotNull('clicked_at')),

                Tables\Filters\Filter::make('failed')
                    ->label('Failed Emails')
                    ->query(fn ($query) => $query->where('status', 'failed')),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->recordUrl(fn ($record) => null);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailLogs::route('/'),
            'view' => Pages\ViewEmailLog::route('/{record}'),
        ];
    }
}
