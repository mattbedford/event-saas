<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketTypes';

    protected static ?string $title = 'Ticket Types & Early Bird Pricing';

    protected static ?string $icon = 'heroicon-o-ticket';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Early Bird, Regular, VIP')
                            ->helperText('The display name for this ticket type'),

                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('CHF')
                            ->helperText('Price for this ticket type'),

                        Forms\Components\DateTimePicker::make('sale_starts_at')
                            ->label('Sale Starts')
                            ->native(false)
                            ->helperText('When this ticket becomes available (leave empty for immediate)')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('sale_ends_at')
                            ->label('Sale Ends')
                            ->native(false)
                            ->helperText('When this ticket stops being available (leave empty for no end)')
                            ->seconds(false),

                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Quantity Available')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Maximum tickets of this type (leave empty for unlimited)')
                            ->suffix('tickets'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive tickets won\'t be available for purchase'),
                    ]),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull()
                    ->helperText('Optional description (shown to customers)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('price')
                    ->money('CHF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->getStatusBadge())
                    ->colors([
                        'success' => 'active',
                        'warning' => 'upcoming',
                        'danger' => 'sold_out',
                        'gray' => fn ($state) => in_array($state, ['inactive', 'ended']),
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'Active',
                        'upcoming' => 'Upcoming',
                        'sold_out' => 'Sold Out',
                        'ended' => 'Ended',
                        'inactive' => 'Inactive',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('sale_starts_at')
                    ->label('Sale Starts')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('Immediately')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_ends_at')
                    ->label('Sale Ends')
                    ->dateTime('M j, Y H:i')
                    ->placeholder('No end date')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_info')
                    ->label('Availability')
                    ->getStateUsing(fn ($record) =>
                        $record->quantity_available
                            ? "{$record->quantity_sold} / {$record->quantity_available} sold"
                            : "{$record->quantity_sold} sold (unlimited)"
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->quantity_available && $record->quantity_sold >= $record->quantity_available
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
