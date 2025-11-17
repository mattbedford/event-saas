<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailChainResource\Pages;
use App\Models\EmailChain;
use App\Models\Event;
use App\Models\EmailTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class EmailChainResource extends Resource
{
    protected static ?string $model = EmailChain::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Email Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Chain Details')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->relationship('event', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Select the event this email chain belongs to'),

                        Forms\Components\Select::make('email_template_id')
                            ->label('Email Template')
                            ->relationship('emailTemplate', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the template to use for this email'),

                        Forms\Components\TextInput::make('order')
                            ->label('Order in Sequence')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Position in the email chain (1 = first email)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active chains will be processed'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Timing Settings')
                    ->schema([
                        Forms\Components\TextInput::make('send_after_minutes')
                            ->label('Send After (minutes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('minutes')
                            ->helperText('How many minutes after registration to send this email'),

                        Forms\Components\Placeholder::make('timing_helper')
                            ->label('Common Timings')
                            ->content(function () {
                                return view('filament.resources.email-chain-resource.timing-helper');
                            }),

                        Forms\Components\Toggle::make('send_only_before_event')
                            ->label('Only Send Before Event')
                            ->default(true)
                            ->helperText('Don\'t send if the calculated send time is after the event date'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('emailTemplate.name')
                    ->label('Template')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('send_after_minutes')
                    ->label('Send After')
                    ->formatStateUsing(function ($state) {
                        if ($state < 60) {
                            return $state . ' min';
                        } elseif ($state < 1440) {
                            $hours = round($state / 60, 1);
                            return $hours . ' hr';
                        } else {
                            $days = round($state / 1440, 1);
                            return $days . ' days';
                        }
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('send_only_before_event')
                    ->label('Before Event Only')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_id', 'asc')
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('send_only_before_event')
                    ->label('Before Event Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()
                                ->title('Chains Activated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()
                                ->title('Chains Deactivated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListEmailChains::route('/'),
            'create' => Pages\CreateEmailChain::route('/create'),
            'edit' => Pages\EditEmailChain::route('/{record}/edit'),
        ];
    }
}
