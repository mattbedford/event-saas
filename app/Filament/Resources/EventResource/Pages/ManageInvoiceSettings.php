<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class ManageInvoiceSettings extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.manage-invoice-settings';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Invoice Settings';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'automatic_invoices_enabled' => $this->record->automatic_invoices_enabled ?? true,
            'invoice_message' => $this->record->invoice_message,
            'invoice_contact_email' => $this->record->invoice_contact_email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Configuration')
                    ->description('Control automatic Stripe invoice generation')
                    ->schema([
                        Forms\Components\Toggle::make('automatic_invoices_enabled')
                            ->label('Automatic Stripe Invoices')
                            ->helperText('Enable automatic invoice generation via Stripe')
                            ->live()
                            ->default(true),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('invoice_message')
                                    ->label('Thank You Message')
                                    ->rows(4)
                                    ->maxLength(500)
                                    ->helperText('Message shown after successful payment')
                                    ->placeholder(fn ($get) =>
                                        $get('automatic_invoices_enabled')
                                            ? 'Thank you for registering! Your invoice will arrive soon via email.'
                                            : 'Thank you for registering! Please contact us to request an official invoice.'
                                    )
                                    ->visible(fn ($get) => !$get('automatic_invoices_enabled')),

                                Forms\Components\TextInput::make('invoice_contact_email')
                                    ->label('Invoice Request Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->helperText('Email address for invoice requests (shown to attendees)')
                                    ->visible(fn ($get) => !$get('automatic_invoices_enabled')),
                            ]),

                        Forms\Components\Placeholder::make('invoice_info_enabled')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <p class="font-semibold mb-2">✓ Automatic Invoices Enabled</p>
                                    <p>Stripe will automatically generate and send invoices after successful payments.</p>
                                </div>
                            '))
                            ->visible(fn ($get) => $get('automatic_invoices_enabled')),

                        Forms\Components\Placeholder::make('invoice_info_disabled')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p class="font-semibold mb-2">Manual Invoice Mode</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Attendees will see custom thank you message</li>
                                        <li>No automatic invoice generation</li>
                                        <li>They must contact you directly for invoices</li>
                                        <li>Provide an invoice contact email for requests</li>
                                    </ul>
                                </div>
                            '))
                            ->visible(fn ($get) => !$get('automatic_invoices_enabled')),
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

        $this->record->update($data);

        \Filament\Notifications\Notification::make()
            ->title('Invoice settings saved')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Invoice Settings',
        ];
    }
}
