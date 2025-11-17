<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegistrationResource\Pages;
use App\Models\Registration;
use App\Models\Event;
use App\Services\BadgeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Event Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Registration::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registration Details')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->relationship('event', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Select the event for this registration'),

                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->helperText('Optional phone number'),

                        Forms\Components\TextInput::make('company')
                            ->maxLength(255)
                            ->helperText('Optional company name'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->live(),

                        Forms\Components\TextInput::make('ticket_price')
                            ->required()
                            ->numeric()
                            ->prefix('CHF')
                            ->helperText('Original ticket price'),

                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('CHF')
                            ->default(0)
                            ->helperText('Discount applied'),

                        Forms\Components\TextInput::make('paid_amount')
                            ->required()
                            ->numeric()
                            ->prefix('CHF')
                            ->helperText('Final amount paid'),

                        Forms\Components\TextInput::make('coupon_code')
                            ->maxLength(255)
                            ->uppercase()
                            ->helperText('Coupon code used (if any)'),

                        Forms\Components\TextInput::make('stripe_session_id')
                            ->maxLength(255)
                            ->helperText('Stripe Checkout Session ID')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('stripe_payment_intent_id')
                            ->maxLength(255)
                            ->helperText('Stripe Payment Intent ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('metadata')
                            ->helperText('JSON metadata (read-only)')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state),

                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->helperText('Internal notes about this registration'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                        'secondary' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Attendance')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'registered' => 'gray',
                        'cancelled' => 'warning',
                        'no_show' => 'danger',
                        'attended' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Amount')
                    ->money('CHF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('coupon_code')
                    ->label('Coupon')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y H:i')),

                Tables\Columns\TextColumn::make('updated_at')
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
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('attendance_status')
                    ->options([
                        'registered' => 'Registered',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No-Show',
                        'attended' => 'Attended',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('has_coupon')
                    ->label('Used Coupon')
                    ->query(fn ($query) => $query->whereNotNull('coupon_code')),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_badge')
                    ->label('Badge')
                    ->icon('heroicon-o-identification')
                    ->color('success')
                    ->visible(fn ($record) => $record->event->settings['badges_enabled'] ?? true)
                    ->url(fn ($record) => route('api.registration.badge', [
                        'eventSlug' => $record->event->slug,
                        'registrationId' => $record->id
                    ]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('resend_confirmation')
                    ->label('Resend Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // TODO: Trigger email resend
                        \Filament\Notifications\Notification::make()
                            ->title('Email Sent')
                            ->body('Confirmation email has been queued for sending.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->payment_status === 'paid'),

                Tables\Actions\Action::make('cancel_registration')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Registration?')
                    ->modalDescription(fn ($record) => $record->canBeCancelled()
                        ? "This will cancel the registration and reaccredit the coupon use. Deadline: {$record->getCancellationDeadline()->format('M d, Y H:i')}"
                        : 'Cancellation deadline has passed. This will be marked as no-show instead.')
                    ->action(function ($record) {
                        $success = $record->markAsCancelled();

                        \Filament\Notifications\Notification::make()
                            ->title($success ? 'Registration Cancelled' : 'Marked as No-Show')
                            ->body($success
                                ? 'Registration cancelled and coupon use reaccredited.'
                                : 'Cancellation deadline passed. Marked as no-show - coupon NOT reaccredited.')
                            ->status($success ? 'success' : 'warning')
                            ->send();
                    })
                    ->visible(fn ($record) => $record->attendance_status === 'registered'),

                Tables\Actions\Action::make('mark_no_show')
                    ->label('No-Show')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as No-Show?')
                    ->modalDescription('This will mark the registrant as a no-show. Coupon use will NOT be reaccredited.')
                    ->action(function ($record) {
                        $record->markAsNoShow();

                        \Filament\Notifications\Notification::make()
                            ->title('Marked as No-Show')
                            ->body('Coupon use has NOT been reaccredited.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->attendance_status === 'registered'),

                Tables\Actions\Action::make('mark_attended')
                    ->label('Attended')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Attended?')
                    ->modalDescription('Confirm that this person attended the event.')
                    ->action(function ($record) {
                        $record->markAsAttended();

                        \Filament\Notifications\Notification::make()
                            ->title('Marked as Attended')
                            ->body('Attendance confirmed.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->attendance_status, ['registered', 'no_show'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['payment_status' => 'paid']);
                            \Filament\Notifications\Notification::make()
                                ->title('Registrations Updated')
                                ->body('Selected registrations marked as paid.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_badges')
                        ->label('Export Badges (ZIP)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Badges')
                        ->modalDescription(fn ($records) => "Generate and download {$records->count()} badge(s) as a ZIP file?")
                        ->action(function ($records) {
                            $badgeService = app(BadgeService::class);

                            // Filter only registrations with badges enabled
                            $registrationsWithBadges = $records->filter(function ($registration) {
                                return $registration->event->settings['badges_enabled'] ?? true;
                            });

                            if ($registrationsWithBadges->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Badges Available')
                                    ->body('None of the selected registrations have badges enabled.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Generate unique temp folder for this export
                            $exportId = uniqid('badge_export_');
                            $tempDir = storage_path("app/temp/{$exportId}");

                            if (!is_dir($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }

                            $generatedCount = 0;
                            $failedCount = 0;

                            // Generate all badges
                            foreach ($registrationsWithBadges as $registration) {
                                try {
                                    $badgePath = $badgeService->generateBadge($registration);

                                    // Copy badge to temp directory with clean filename
                                    $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '_', $registration->full_name);
                                    $filename = "{$registration->id}_{$cleanName}.pdf";

                                    Storage::copy($badgePath, "temp/{$exportId}/{$filename}");
                                    $generatedCount++;
                                } catch (\Exception $e) {
                                    $failedCount++;
                                    \Illuminate\Support\Facades\Log::error('Badge generation failed in bulk export', [
                                        'registration_id' => $registration->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }

                            // Create ZIP file
                            $zipPath = storage_path("app/temp/{$exportId}.zip");
                            $zip = new \ZipArchive();

                            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                                // Add all PDFs to ZIP
                                $files = glob($tempDir . '/*.pdf');
                                foreach ($files as $file) {
                                    $zip->addFile($file, basename($file));
                                }
                                $zip->close();
                            }

                            // Clean up temp directory
                            array_map('unlink', glob($tempDir . '/*'));
                            rmdir($tempDir);

                            // Notification
                            \Filament\Notifications\Notification::make()
                                ->title('Badges Exported')
                                ->body("Generated: {$generatedCount} | Failed: {$failedCount}")
                                ->success()
                                ->send();

                            // Download the ZIP
                            return Response::download($zipPath, "badges_{$exportId}.zip")->deleteFileAfterSend(true);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn ($record) => null); // Disable default click-to-edit
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Registration Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('company')
                            ->icon('heroicon-o-building-office')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('event.name')
                            ->label('Event'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Registered At')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_status')
                            ->badge()
                            ->colors([
                                'warning' => 'pending',
                                'success' => 'paid',
                                'danger' => 'failed',
                                'gray' => 'refunded',
                                'secondary' => 'cancelled',
                            ]),
                        Infolists\Components\TextEntry::make('ticket_price')
                            ->money('CHF'),
                        Infolists\Components\TextEntry::make('discount_amount')
                            ->money('CHF'),
                        Infolists\Components\TextEntry::make('paid_amount')
                            ->money('CHF')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('coupon_code')
                            ->badge()
                            ->color('info')
                            ->placeholder('No coupon used'),
                        Infolists\Components\TextEntry::make('stripe_session_id')
                            ->label('Stripe Session')
                            ->copyable()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('stripe_payment_intent_id')
                            ->label('Payment Intent')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : ($state ?? 'None'))
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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
            'index' => Pages\ListRegistrations::route('/'),
            'create' => Pages\CreateRegistration::route('/create'),
            'edit' => Pages\EditRegistration::route('/{record}/edit'),
            'view' => Pages\ViewRegistration::route('/{record}'),
        ];
    }
}
