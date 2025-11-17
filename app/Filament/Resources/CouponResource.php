<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\Event;
use App\Services\CouponManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Details')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->relationship('event', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('code')
                            ->label('Coupon Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->uppercase()
                            ->helperText('Will be automatically uppercased')
                            ->rules(['regex:/^[A-Z0-9\-]+$/']),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company/Contact Name')
                            ->maxLength(255)
                            ->helperText('Optional: Display name for this coupon'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('hubspot_company_id')
                                    ->label('Hubspot Company ID')
                                    ->helperText('Link to Hubspot company'),

                                Forms\Components\TextInput::make('hubspot_contact_id')
                                    ->label('Hubspot Contact ID')
                                    ->helperText('Link to Hubspot contact'),
                            ]),
                    ]),

                Forms\Components\Section::make('Discount Configuration')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount (CHF)',
                            ])
                            ->required()
                            ->default('percentage')
                            ->live(),

                        Forms\Components\TextInput::make('discount_value')
                            ->label('Discount Value')
                            ->required()
                            ->numeric()
                            ->suffix(fn ($get) => $get('discount_type') === 'percentage' ? '%' : 'CHF')
                            ->helperText(fn ($get) =>
                                $get('discount_type') === 'percentage'
                                    ? 'Enter 100 for 100% off'
                                    : 'Fixed amount in CHF'
                            ),

                        Forms\Components\TextInput::make('max_uses')
                            ->label('Maximum Uses')
                            ->numeric()
                            ->helperText('Leave empty for unlimited uses'),
                    ]),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Valid From')
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Valid Until')
                            ->native(false)
                            ->helperText('Leave empty for no expiration date'),

                        Forms\Components\TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->default(now()->year)
                            ->helperText('Coupon will expire after this year'),
                    ]),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\Select::make('restricted_to_event_id')
                            ->label('Restrict to Specific Event')
                            ->relationship('restrictedToEvent', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Limit this coupon to a specific event only'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_manual')
                            ->label('Manual Coupon')
                            ->default(true)
                            ->helperText('Manually created vs auto-generated'),

                        Forms\Components\TextInput::make('generated_by')
                            ->label('Generated By')
                            ->default(fn () => auth()->user()?->name)
                            ->helperText('Person who created this coupon'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Code copied!')
                    ->icon('heroicon-o-ticket')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Company/Name')
                    ->searchable(['company_name', 'code'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($record) =>
                        $record->discount_type === 'percentage'
                            ? $record->discount_value . '%'
                            : 'CHF ' . number_format($record->discount_value, 2)
                    )
                    ->badge()
                    ->color(fn ($record) => $record->discount_value == 100 ? 'success' : 'info'),

                Tables\Columns\TextColumn::make('used_count')
                    ->label('Uses')
                    ->formatStateUsing(fn ($record) =>
                        $record->max_uses
                            ? "{$record->used_count} / {$record->max_uses}"
                            : $record->used_count
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->badge()
                    ->color(fn ($state) => $state == now()->year ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_manual')
                    ->label('Manual')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('hasHubspotLink')
                    ->label('Hubspot')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasHubspotLink())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => collect(range(now()->year - 2, now()->year + 1))
                        ->mapWithKeys(fn ($year) => [$year => $year])
                    )
                    ->default(now()->year),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(true),

                Tables\Filters\TernaryFilter::make('is_manual')
                    ->label('Manual Coupons'),

                Tables\Filters\Filter::make('hubspot_linked')
                    ->label('Hubspot Linked')
                    ->query(fn (Builder $query) => $query->linkedToHubspot()),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired by Year')
                    ->query(fn (Builder $query) => $query->where('year', '<', now()->year)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_stats')
                        ->label('View Statistics')
                        ->icon('heroicon-o-chart-bar')
                        ->modalHeading(fn ($record) => "Statistics for {$record->code}")
                        ->modalContent(fn ($record) => view('filament.resources.coupon-resource.stats', [
                            'coupon' => $record,
                            'stats' => app(CouponManagementService::class)->getCouponStats($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_generate')
                    ->label('Bulk Generate from Hubspot')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->options(Event::pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('hubspot_list_id')
                            ->label('Hubspot List ID')
                            ->required()
                            ->helperText('Enter the Hubspot list ID to fetch companies from'),

                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount (CHF)',
                            ])
                            ->required()
                            ->default('percentage'),

                        Forms\Components\TextInput::make('discount_value')
                            ->label('Discount Value')
                            ->required()
                            ->numeric()
                            ->default(100),

                        Forms\Components\TextInput::make('max_uses')
                            ->label('Max Uses Per Company')
                            ->numeric()
                            ->default(10)
                            ->helperText('How many times each company can use their coupon'),

                        Forms\Components\TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->default(now()->year),
                    ])
                    ->action(function (array $data) {
                        $couponService = app(CouponManagementService::class);
                        $event = Event::find($data['event_id']);

                        $results = $couponService->bulkGenerateFromHubspotList(
                            $data['hubspot_list_id'],
                            $event,
                            [
                                'discount_type' => $data['discount_type'],
                                'discount_value' => $data['discount_value'],
                                'max_uses' => $data['max_uses'],
                                'year' => $data['year'],
                            ]
                        );

                        Notification::make()
                            ->title('Bulk Generation Complete')
                            ->body(sprintf(
                                'Created: %d | Skipped: %d | Errors: %d',
                                count($results['created']),
                                count($results['skipped']),
                                count($results['errors'])
                            ))
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)
            ->where('year', now()->year)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
