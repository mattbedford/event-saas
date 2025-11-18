<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class ManageIntegrations extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.manage-integrations';

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $title = 'Integrations';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'stripe_product_id' => $this->record->stripe_product_id,
            'hubspot_list_id' => $this->record->hubspot_list_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stripe Configuration')
                    ->description('Shared Stripe credentials are configured in .env. Enter your event-specific Product ID here.')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_product_id')
                            ->label('Stripe Product ID')
                            ->helperText('Optional. Creates dynamic prices for this product. Leave empty to use inline pricing.')
                            ->prefix('prod_')
                            ->maxLength(255),

                        Forms\Components\Placeholder::make('stripe_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p class="font-semibold mb-2">How it works:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>If you provide a Product ID, the system will create dynamic prices</li>
                                        <li>If left empty, inline checkout pricing will be used</li>
                                        <li>Ensure the product exists in your Stripe account</li>
                                    </ul>
                                </div>
                            ')),
                    ]),

                Forms\Components\Section::make('HubSpot Configuration')
                    ->description('Shared HubSpot credentials are configured in .env. Enter your event-specific List ID here.')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\TextInput::make('hubspot_list_id')
                            ->label('HubSpot List ID')
                            ->helperText('Registrants will be automatically added to this list')
                            ->numeric()
                            ->maxLength(255),

                        Forms\Components\Placeholder::make('hubspot_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p class="font-semibold mb-2">Integration Details:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Confirmed registrations will be synced to this list</li>
                                        <li>Contact information and custom properties will be updated</li>
                                        <li>Find your List ID in HubSpot > Contacts > Lists</li>
                                    </ul>
                                </div>
                            ')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save Integrations')
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

        $this->record->stripe_product_id = $data['stripe_product_id'];
        $this->record->hubspot_list_id = $data['hubspot_list_id'];
        $this->record->save();

        \Filament\Notifications\Notification::make()
            ->title('Integration settings saved')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Integrations',
        ];
    }
}
