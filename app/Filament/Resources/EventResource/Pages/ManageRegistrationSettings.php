<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Resources\Pages\Page;

class ManageRegistrationSettings extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.manage-registration-settings';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Registration Settings';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'registrations_enabled' => $this->record->settings['registrations_enabled'] ?? true,
            'registration_status_message_type' => $this->record->settings['registration_status_message_type'] ?? 'not_open',
            'registration_status_message' => $this->record->settings['registration_status_message'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Registration Status')
                    ->description('Control whether registrations are open for this event')
                    ->schema([
                        Forms\Components\Toggle::make('registrations_enabled')
                            ->label('Registrations Open')
                            ->default(true)
                            ->live()
                            ->helperText('Toggle to open/close registrations'),

                        Forms\Components\Select::make('registration_status_message_type')
                            ->label('Status Message When Closed')
                            ->options([
                                'not_open' => 'Not Open Yet',
                                'closed' => 'Closed',
                                'sold_out' => 'Sold Out',
                                'custom' => 'Custom Message',
                            ])
                            ->visible(fn (callable $get) => !$get('registrations_enabled'))
                            ->live(),

                        Forms\Components\Textarea::make('registration_status_message')
                            ->label('Custom Status Message')
                            ->visible(fn (callable $get) =>
                                !$get('registrations_enabled') &&
                                $get('registration_status_message_type') === 'custom'
                            )
                            ->helperText('HTML allowed')
                            ->rows(4),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->icon('heroicon-o-check')
                ->color('primary'),

            Actions\Action::make('back')
                ->label('Back to Event')
                ->url(fn (): string => EventResource::getUrl('edit', ['record' => $this->record]))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = $this->record->settings ?? [];
        $settings['registrations_enabled'] = $data['registrations_enabled'];
        $settings['registration_status_message_type'] = $data['registration_status_message_type'] ?? null;
        $settings['registration_status_message'] = $data['registration_status_message'] ?? null;

        $this->record->settings = $settings;
        $this->record->save();

        \Filament\Notifications\Notification::make()
            ->title('Registration settings saved')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Registration Settings',
        ];
    }
}
