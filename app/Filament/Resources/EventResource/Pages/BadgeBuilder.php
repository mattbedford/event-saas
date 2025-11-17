<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class BadgeBuilder extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.badge-builder';

    public ?array $data = [];
    public Event $record;

    public function mount(): void
    {
        $this->record = $this->getRecord();

        // Load existing badge template or set defaults
        $this->data = $this->record->settings['badge_template'] ?? [
            'width' => 400, // 4 inches in pixels at 100dpi
            'height' => 300, // 3 inches
            'background_pdf' => null,
            'background_color' => '#667eea',
            'logo' => null,
            'logo_position' => ['x' => 50, 'y' => 20],
            'logo_size' => ['width' => 100, 'height' => 50],
            'fields' => [
                [
                    'name' => 'full_name',
                    'label' => 'Full Name',
                    'position' => ['x' => 200, 'y' => 150],
                    'font_size' => 24,
                    'font_weight' => 'bold',
                    'color' => '#ffffff',
                    'align' => 'center',
                ],
                [
                    'name' => 'company',
                    'label' => 'Company',
                    'position' => ['x' => 200, 'y' => 180],
                    'font_size' => 14,
                    'font_weight' => 'normal',
                    'color' => '#ffffff',
                    'align' => 'center',
                ],
            ],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Background')
                    ->schema([
                        Forms\Components\FileUpload::make('background_pdf')
                            ->label('Background PDF (Optional)')
                            ->disk('public')
                            ->directory('badge-backgrounds')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120),

                        Forms\Components\ColorPicker::make('background_color')
                            ->label('Background Color (if no PDF)')
                            ->default('#667eea'),
                    ]),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Event Logo')
                            ->disk('public')
                            ->directory('badge-logos')
                            ->image()
                            ->maxSize(2048),
                    ]),

                Forms\Components\Section::make('Dimensions')
                    ->schema([
                        Forms\Components\TextInput::make('width')
                            ->label('Width (pixels)')
                            ->numeric()
                            ->default(400)
                            ->required(),

                        Forms\Components\TextInput::make('height')
                            ->label('Height (pixels)')
                            ->numeric()
                            ->default(300)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicate from Event')
                ->icon('heroicon-o-document-duplicate')
                ->form([
                    Forms\Components\Select::make('source_event_id')
                        ->label('Copy template from')
                        ->options(
                            Event::where('id', '!=', $this->record->id)
                                ->whereNotNull('settings->badge_template')
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                ])
                ->action('duplicateTemplate'),

            Action::make('preview')
                ->label('Preview & Download')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->action('downloadPreview'),

            Action::make('save')
                ->label('Save Template')
                ->icon('heroicon-o-check')
                ->action('saveTemplate')
                ->color('success'),
        ];
    }

    public function saveTemplate(): void
    {
        $settings = $this->record->settings ?? [];
        $settings['badge_template'] = $this->data;

        $this->record->update(['settings' => $settings]);

        Notification::make()
            ->title('Badge template saved successfully')
            ->success()
            ->send();
    }

    public function updateFieldPosition(string $fieldName, int $x, int $y): void
    {
        $fields = $this->data['fields'] ?? [];

        foreach ($fields as $index => $field) {
            if ($field['name'] === $fieldName) {
                $this->data['fields'][$index]['position'] = ['x' => $x, 'y' => $y];
                break;
            }
        }
    }

    public function updateLogoPosition(int $x, int $y): void
    {
        $this->data['logo_position'] = ['x' => $x, 'y' => $y];
    }

    public function addField(string $fieldName, string $label): void
    {
        $this->data['fields'][] = [
            'name' => $fieldName,
            'label' => $label,
            'position' => ['x' => 200, 'y' => 100],
            'font_size' => 14,
            'font_weight' => 'normal',
            'color' => '#ffffff',
            'align' => 'center',
        ];
    }

    public function removeField(int $index): void
    {
        unset($this->data['fields'][$index]);
        $this->data['fields'] = array_values($this->data['fields']);
    }

    public function updateFieldStyle(int $index, array $style): void
    {
        foreach ($style as $key => $value) {
            $this->data['fields'][$index][$key] = $value;
        }
    }

    public function duplicateTemplate(array $data): void
    {
        $sourceEvent = Event::find($data['source_event_id']);

        if (!$sourceEvent || !isset($sourceEvent->settings['badge_template'])) {
            Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();
            return;
        }

        $this->data = $sourceEvent->settings['badge_template'];

        Notification::make()
            ->title('Template duplicated successfully')
            ->body('Template copied from ' . $sourceEvent->name . '. Click "Save Template" to apply.')
            ->success()
            ->send();
    }

    public function downloadPreview(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Create a mock registration for preview
        $mockRegistration = new \App\Models\Registration([
            'event_id' => $this->record->id,
            'name' => 'John',
            'surname' => 'Doe',
            'full_name' => 'John Doe',
            'company' => 'Acme Corporation',
            'email' => 'john.doe@example.com',
        ]);
        $mockRegistration->event = $this->record;

        // Temporarily save current template to event for preview
        $originalSettings = $this->record->settings;
        $tempSettings = $originalSettings ?? [];
        $tempSettings['badge_template'] = $this->data;
        $this->record->settings = $tempSettings;

        // Generate PDF
        $template = $this->data;
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('badges.custom-template', [
            'registration' => $mockRegistration,
            'event' => $this->record,
            'template' => $template,
        ]);

        $width = ($template['width'] ?? 400) * 0.75;
        $height = ($template['height'] ?? 300) * 0.75;
        $pdf->setPaper([0, 0, $width, $height], 'landscape');

        // Restore original settings
        $this->record->settings = $originalSettings;

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'badge-preview.pdf');
    }
}
