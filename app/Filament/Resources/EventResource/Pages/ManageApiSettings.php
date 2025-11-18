<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class ManageApiSettings extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.manage-api-settings';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $title = 'API & Webhooks';

    public ?array $data = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'api_key' => $this->record->api_key,
            'webhook_url' => $this->record->webhook_url,
            'webhook_events' => $this->record->webhook_events ?? [],
            'webhook_secret' => $this->record->webhook_secret,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('API Authentication')
                    ->description('Secure API key for event-specific operations')
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->placeholder('Click "Generate API Key" to create')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy_api_key')
                                    ->icon('heroicon-o-clipboard')
                                    ->action(function () {
                                        Notification::make()
                                            ->title('API Key copied!')
                                            ->body('Use this key in the X-Event-API-Key header')
                                            ->success()
                                            ->send();
                                    })
                                    ->visible(fn ($get) => !empty($get('api_key')))
                            ),

                        Forms\Components\Placeholder::make('api_key_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                    <p class="font-semibold mb-2">🔑 API Key Usage</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>Include in header: <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded">X-Event-API-Key: {your_key}</code></li>
                                        <li>Scoped to this event only</li>
                                        <li>Required for event-specific API endpoints</li>
                                        <li>Keep secure - treat like a password</li>
                                    </ul>
                                </div>
                            '))
                            ->visible(fn ($get) => !empty($get('api_key'))),

                        Forms\Components\Placeholder::make('no_api_key')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg">
                                    <p class="font-semibold mb-1">⚠️ No API Key Generated</p>
                                    <p class="text-xs">Click "Generate API Key" in the header to create an API key for this event.</p>
                                </div>
                            '))
                            ->visible(fn ($get) => empty($get('api_key'))),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Webhook Configuration')
                    ->description('Receive real-time notifications about event changes')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->placeholder('https://your-domain.com/webhooks/events')
                            ->helperText('HTTPS endpoint to receive webhook notifications')
                            ->maxLength(500),

                        Forms\Components\CheckboxList::make('webhook_events')
                            ->label('Events to Monitor')
                            ->options([
                                'registration.created' => 'Registration Created',
                                'registration.updated' => 'Registration Updated',
                                'registration.cancelled' => 'Registration Cancelled',
                                'payment.succeeded' => 'Payment Succeeded',
                                'payment.failed' => 'Payment Failed',
                                'coupon.used' => 'Coupon Used',
                                'event.updated' => 'Event Updated',
                                'event.seats_nearly_full' => 'Seats Nearly Full (90%)',
                            ])
                            ->columns(2)
                            ->helperText('Select which events should trigger webhooks'),

                        Forms\Components\TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->placeholder('Click "Generate Webhook Secret" to create')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Used to verify webhook authenticity')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy_webhook_secret')
                                    ->icon('heroicon-o-clipboard')
                                    ->action(function () {
                                        Notification::make()
                                            ->title('Webhook Secret copied!')
                                            ->success()
                                            ->send();
                                    })
                                    ->visible(fn ($get) => !empty($get('webhook_secret')))
                            ),

                        Forms\Components\Placeholder::make('webhook_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                                    <p class="font-semibold mb-2">📡 Webhook Format</p>
                                    <p class="text-xs mb-2">POST request with JSON payload:</p>
                                    <pre class="bg-gray-200 dark:bg-gray-700 p-2 rounded text-xs overflow-x-auto"><code>{
  "event": "registration.created",
  "event_slug": "' . $this->record->slug . '",
  "timestamp": 1234567890,
  "data": { ... },
  "signature": "sha256_hash"
}</code></pre>
                                    <p class="text-xs mt-2">Verify signature using webhook secret with HMAC-SHA256</p>
                                </div>
                            '))
                            ->visible(fn ($get) => !empty($get('webhook_url'))),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_api_key')
                ->label('Generate API Key')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate New API Key')
                ->modalDescription('This will replace your existing API key. Any integrations using the old key will stop working.')
                ->modalSubmitActionLabel('Generate')
                ->action('generateApiKey')
                ->visible(fn () => !empty($this->record->api_key)),

            Actions\Action::make('create_api_key')
                ->label('Generate API Key')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->action('generateApiKey')
                ->visible(fn () => empty($this->record->api_key)),

            Actions\Action::make('generate_webhook_secret')
                ->label('Generate Webhook Secret')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate New Webhook Secret')
                ->modalDescription('This will replace your existing webhook secret. Update your webhook verification logic with the new secret.')
                ->modalSubmitActionLabel('Generate')
                ->action('generateWebhookSecret')
                ->visible(fn () => !empty($this->record->webhook_secret)),

            Actions\Action::make('create_webhook_secret')
                ->label('Generate Webhook Secret')
                ->icon('heroicon-o-shield-check')
                ->color('primary')
                ->action('generateWebhookSecret')
                ->visible(fn () => empty($this->record->webhook_secret)),

            Actions\Action::make('test_webhook')
                ->label('Test Webhook')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('testWebhook')
                ->visible(fn () => !empty($this->record->webhook_url)),

            Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->icon('heroicon-o-check')
                ->color('success'),

            Actions\Action::make('back')
                ->label('Back to Event')
                ->url(fn (): string => EventResource::getUrl('edit', ['record' => $this->record]))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    public function generateApiKey(): void
    {
        $apiKey = $this->record->generateApiKey();

        $this->form->fill(['api_key' => $apiKey]);

        Notification::make()
            ->title('API Key Generated')
            ->body('Copy and save your API key securely. It won\'t be shown again.')
            ->success()
            ->persistent()
            ->send();
    }

    public function generateWebhookSecret(): void
    {
        $secret = $this->record->generateWebhookSecret();

        $this->form->fill(['webhook_secret' => $secret]);

        Notification::make()
            ->title('Webhook Secret Generated')
            ->body('Copy and save your webhook secret securely.')
            ->success()
            ->persistent()
            ->send();
    }

    public function testWebhook(): void
    {
        // Send a test webhook
        $payload = [
            'event' => 'test.webhook',
            'event_slug' => $this->record->slug,
            'timestamp' => now()->timestamp,
            'data' => [
                'message' => 'This is a test webhook from your event management system',
                'event_name' => $this->record->name,
            ],
        ];

        // Add signature if secret exists
        if ($this->record->webhook_secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->record->webhook_secret);
            $payload['signature'] = $signature;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post($this->record->webhook_url, $payload);

            if ($response->successful()) {
                Notification::make()
                    ->title('Test Webhook Sent')
                    ->body('Status: ' . $response->status() . ' - Webhook endpoint responded successfully')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Webhook Failed')
                    ->body('Status: ' . $response->status() . ' - Check your endpoint')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Webhook Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update([
            'webhook_url' => $data['webhook_url'],
            'webhook_events' => $data['webhook_events'],
        ]);

        Notification::make()
            ->title('API settings saved')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'API & Webhooks',
        ];
    }
}
