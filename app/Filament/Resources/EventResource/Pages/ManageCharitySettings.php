<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class ManageCharitySettings extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.manage-charity-settings';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $title = 'Charity Donation';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'charity_enabled' => $this->record->charity_enabled,
            'charity_name' => $this->record->charity_name,
            'charity_description' => $this->record->charity_description,
            'charity_logo_url' => $this->record->charity_logo_url,
            'charity_website_url' => $this->record->charity_website_url,
            'charity_donation_url' => $this->record->charity_donation_url,
            'charity_suggested_amount' => $this->record->charity_suggested_amount,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Charity Configuration')
                    ->description('Add an optional charity donation opportunity at checkout')
                    ->schema([
                        Forms\Components\Toggle::make('charity_enabled')
                            ->label('Enable Charity Donation')
                            ->helperText('Show charity donation option during checkout')
                            ->live(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('charity_name')
                                    ->label('Charity Name')
                                    ->required(fn ($get) => $get('charity_enabled'))
                                    ->maxLength(255)
                                    ->visible(fn ($get) => $get('charity_enabled')),

                                Forms\Components\TextInput::make('charity_suggested_amount')
                                    ->label('Suggested Donation Amount')
                                    ->numeric()
                                    ->prefix('CHF')
                                    ->helperText('Optional suggested amount')
                                    ->visible(fn ($get) => $get('charity_enabled')),

                                Forms\Components\TextInput::make('charity_logo_url')
                                    ->label('Charity Logo URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->helperText('URL to charity logo image')
                                    ->visible(fn ($get) => $get('charity_enabled')),

                                Forms\Components\TextInput::make('charity_website_url')
                                    ->label('Charity Website URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->helperText('Link to charity website')
                                    ->visible(fn ($get) => $get('charity_enabled')),

                                Forms\Components\TextInput::make('charity_donation_url')
                                    ->label('Direct Donation URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->helperText('Direct link for attendees to donate')
                                    ->columnSpanFull()
                                    ->visible(fn ($get) => $get('charity_enabled')),

                                Forms\Components\Textarea::make('charity_description')
                                    ->label('Charity Description')
                                    ->rows(4)
                                    ->maxLength(1000)
                                    ->helperText('Brief description shown at checkout')
                                    ->columnSpanFull()
                                    ->visible(fn ($get) => $get('charity_enabled')),
                            ]),

                        Forms\Components\Placeholder::make('charity_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p class="font-semibold mb-2">How it works:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>During checkout, attendees will see charity information</li>
                                        <li>They can click the donation URL to support the charity</li>
                                        <li>No payment integration - external link only</li>
                                        <li>Logo and description help attendees learn about the cause</li>
                                    </ul>
                                </div>
                            '))
                            ->visible(fn ($get) => $get('charity_enabled')),
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
            ->title('Charity settings saved')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Charity Settings',
        ];
    }
}
