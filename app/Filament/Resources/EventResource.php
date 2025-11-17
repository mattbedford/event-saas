<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Event Configuration')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('slug', \Illuminate\Support\Str::slug($state))
                                    ),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Used in URLs and API endpoints'),

                                Forms\Components\TextInput::make('ticket_price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('CHF')
                                    ->helperText('Base ticket price before discounts'),

                                Forms\Components\TextInput::make('max_seats')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Maximum capacity. Leave empty for unlimited seats.')
                                    ->suffix('seats'),

                                Forms\Components\DateTimePicker::make('event_date')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Inactive events won\'t be available via API'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Registration Settings')
                            ->schema([
                                Forms\Components\Toggle::make('settings.registrations_enabled')
                                    ->label('Registrations Open')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Toggle to open/close registrations'),

                                Forms\Components\Select::make('settings.registration_status_message_type')
                                    ->label('Status Message')
                                    ->options([
                                        'not_open' => 'Not Open Yet',
                                        'closed' => 'Closed',
                                        'sold_out' => 'Sold Out',
                                        'custom' => 'Custom Message',
                                    ])
                                    ->visible(fn (callable $get) => !$get('settings.registrations_enabled'))
                                    ->live(),

                                Forms\Components\Textarea::make('settings.registration_status_message')
                                    ->label('Custom Status Message')
                                    ->visible(fn (callable $get) =>
                                        !$get('settings.registrations_enabled') &&
                                        $get('settings.registration_status_message_type') === 'custom'
                                    )
                                    ->helperText('HTML allowed'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Badge Settings')
                            ->schema([
                                Forms\Components\Toggle::make('settings.badges_enabled')
                                    ->label('Enable Badge Generation')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Turn off if this event doesn\'t need badges'),

                                Forms\Components\Toggle::make('settings.badge_barcode_enabled')
                                    ->label('Include Barcode on Badges')
                                    ->default(false)
                                    ->visible(fn (callable $get) => $get('settings.badges_enabled'))
                                    ->helperText('Adds QR code with registration ID and event info'),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('configure_badge_template')
                                        ->label('Configure Badge Template')
                                        ->icon('heroicon-o-paint-brush')
                                        ->url(fn ($record) => $record ? route('filament.admin.resources.events.badge-builder', $record) : null)
                                        ->visible(fn ($record, callable $get) => $record && $get('settings.badges_enabled')),
                                ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Integrations')
                            ->schema([
                                Forms\Components\Section::make('Stripe Configuration')
                                    ->description('Shared Stripe credentials are configured in .env. Enter your event-specific Product ID here.')
                                    ->schema([
                                        Forms\Components\TextInput::make('stripe_product_id')
                                            ->label('Stripe Product ID')
                                            ->helperText('Optional. Creates dynamic prices for this product. Leave empty to use inline pricing.')
                                            ->prefix('prod_'),
                                    ]),

                                Forms\Components\Section::make('Hubspot Configuration')
                                    ->description('Shared Hubspot credentials are configured in .env. Enter your event-specific List ID here.')
                                    ->schema([
                                        Forms\Components\TextInput::make('hubspot_list_id')
                                            ->label('Hubspot List ID')
                                            ->helperText('Registrants will be added to this list')
                                            ->numeric(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->icon('heroicon-o-link'),

                Tables\Columns\TextColumn::make('ticket_price')
                    ->money('CHF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_seats')
                    ->label('Capacity')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? 'Unlimited')
                    ->badge()
                    ->color(fn ($state) => $state ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('registrations_count')
                    ->counts([
                        'registrations' => fn ($query) => $query->whereIn('payment_status', ['paid', 'pending'])
                    ])
                    ->label('Registered')
                    ->badge()
                    ->color(fn ($record) => $record->isNearlyFull() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('capacity_info')
                    ->label('Available')
                    ->state(fn ($record) => $record->remainingSeats())
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null) {
                            return '∞';
                        }
                        $percentage = $record->capacityPercentage();
                        return "{$state} ({$percentage}% free)";
                    })
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        !$record->hasAvailableSeats() => 'danger',
                        $record->isNearlyFull() => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('event_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('settings.registrations_enabled')
                    ->label('Reg. Open')
                    ->boolean(),

                Tables\Columns\IconColumn::make('settings.badges_enabled')
                    ->label('Badges')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Events')
                    ->default(true),

                Tables\Filters\TernaryFilter::make('settings.registrations_enabled')
                    ->label('Registrations Open'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('badge_builder')
                    ->label('Badges')
                    ->icon('heroicon-o-paint-brush')
                    ->url(fn ($record) => route('filament.admin.resources.events.badge-builder', $record))
                    ->visible(fn ($record) => $record->settings['badges_enabled'] ?? true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'badge-builder' => Pages\BadgeBuilder::route('/{record}/badge-builder'),
        ];
    }
}
