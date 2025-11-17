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
            Action::make('save')
                ->label('Save Template')
                ->action('saveTemplate')
                ->color('success'),

            Action::make('preview')
                ->label('Preview Badge')
                ->action('previewBadge')
                ->color('primary')
                ->openUrlInNewTab()
                ->url(fn () => route('filament.admin.resources.events.badge-preview', ['record' => $this->record])),
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
}
