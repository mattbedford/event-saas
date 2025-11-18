<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaitlistResource\Pages;
use App\Models\Waitlist;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class WaitlistResource extends Resource
{
    protected static ?string $model = Waitlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $waiting = Waitlist::where('status', 'waiting')->count();
        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Waitlist Entry')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->relationship('event', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('company')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->options([
                                'waiting' => 'Waiting',
                                'notified' => 'Notified',
                                'registered' => 'Registered',
                                'expired' => 'Expired',
                            ])
                            ->required()
                            ->default('waiting'),

                        Forms\Components\DateTimePicker::make('notified_at')
                            ->label('Notified At')
                            ->native(false)
                            ->visible(fn ($get) => in_array($get('status'), ['notified', 'registered', 'expired'])),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Notification Expires At')
                            ->native(false)
                            ->helperText('After this time, the slot will be offered to the next person')
                            ->visible(fn ($get) => $get('status') === 'notified'),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->getStateUsing(fn ($record) => $record->status === 'waiting' ? $record->getPosition() : '—')
                    ->badge()
                    ->color('info')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'waiting',
                        'info' => 'notified',
                        'success' => 'registered',
                        'gray' => 'expired',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Notified')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) =>
                        $record->expires_at && $record->status === 'notified'
                            ? ($record->isExpired() ? 'Expired!' : $record->expires_at->diffForHumans())
                            : null
                    )
                    ->color(fn ($record) =>
                        $record->isExpired() ? 'danger' : 'gray'
                    )
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined Waitlist')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'waiting' => 'Waiting',
                        'notified' => 'Notified',
                        'registered' => 'Registered',
                        'expired' => 'Expired',
                    ])
                    ->default('waiting'),
            ])
            ->actions([
                Tables\Actions\Action::make('notify')
                    ->label('Notify')
                    ->icon('heroicon-o-bell')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'waiting')
                    ->requiresConfirmation()
                    ->modalHeading('Notify Waitlist Entry')
                    ->modalDescription('Send notification email that a spot is available. They will have 24 hours to register.')
                    ->action(function ($record) {
                        $record->markAsNotified();

                        // TODO: Send notification email

                        Notification::make()
                            ->title('Notification sent')
                            ->success()
                            ->body("Notified {$record->full_name}. They have until " . $record->expires_at->format('M d, Y H:i') . " to register.")
                            ->send();
                    }),

                Tables\Actions\Action::make('register')
                    ->label('Mark Registered')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['waiting', 'notified']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->markAsRegistered();

                        Notification::make()
                            ->title('Marked as registered')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('notify_bulk')
                        ->label('Notify Selected')
                        ->icon('heroicon-o-bell')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'waiting') {
                                    $record->markAsNotified();
                                    $count++;
                                    // TODO: Send notification email
                                }
                            }

                            Notification::make()
                                ->title("Notified {$count} people")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWaitlists::route('/'),
            'create' => Pages\CreateWaitlist::route('/create'),
            'edit' => Pages\EditWaitlist::route('/{record}/edit'),
        ];
    }
}
