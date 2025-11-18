<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class EmailBuilder extends Page
{
    protected static string $resource = EmailTemplateResource::class;

    protected static string $view = 'filament.resources.email-template-resource.pages.email-builder';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $title = 'Email Builder';

    public ?array $data = [];
    public EmailTemplate $record;

    // Available block types
    public array $availableBlocks = [
        [
            'type' => 'text',
            'label' => 'Text Block',
            'icon' => 'text',
            'default' => [
                'content' => 'Enter your text here...',
                'alignment' => 'left',
                'font_size' => '14',
                'color' => '#333333',
            ],
        ],
        [
            'type' => 'heading',
            'label' => 'Heading',
            'icon' => 'heading',
            'default' => [
                'content' => 'Your Heading',
                'alignment' => 'left',
                'font_size' => '24',
                'color' => '#1e73be',
            ],
        ],
        [
            'type' => 'button',
            'label' => 'Button',
            'icon' => 'button',
            'default' => [
                'text' => 'Click Here',
                'url' => '#',
                'alignment' => 'center',
                'bg_color' => '#1e73be',
                'text_color' => '#ffffff',
                'border_radius' => '4',
            ],
        ],
        [
            'type' => 'image',
            'label' => 'Image',
            'icon' => 'image',
            'default' => [
                'url' => '',
                'alt' => 'Image',
                'width' => '100',
                'alignment' => 'center',
            ],
        ],
        [
            'type' => 'spacer',
            'label' => 'Spacer',
            'icon' => 'spacer',
            'default' => [
                'height' => '20',
            ],
        ],
        [
            'type' => 'divider',
            'label' => 'Divider',
            'icon' => 'divider',
            'default' => [
                'color' => '#e5e7eb',
                'thickness' => '1',
            ],
        ],
    ];

    public function mount(): void
    {
        $this->record = $this->getRecord();

        // Load existing email structure or set defaults
        $this->data = $this->record->settings['email_structure'] ?? $this->getDefaultStructure();
    }

    protected function getDefaultStructure(): array
    {
        return [
            'blocks' => [
                [
                    'type' => 'heading',
                    'content' => 'Welcome to {{event_name}}',
                    'alignment' => 'center',
                    'font_size' => '28',
                    'color' => '#1e73be',
                ],
                [
                    'type' => 'spacer',
                    'height' => '20',
                ],
                [
                    'type' => 'text',
                    'content' => 'Hi {{full_name}},',
                    'alignment' => 'left',
                    'font_size' => '14',
                    'color' => '#333333',
                ],
                [
                    'type' => 'text',
                    'content' => 'Thank you for registering!',
                    'alignment' => 'left',
                    'font_size' => '14',
                    'color' => '#333333',
                ],
            ],
            'settings' => [
                'background_color' => '#f9fafb',
                'content_width' => '600',
                'font_family' => 'Arial, sans-serif',
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicate from Template')
                ->icon('heroicon-o-document-duplicate')
                ->form([
                    \Filament\Forms\Components\Select::make('source_template_id')
                        ->label('Copy structure from')
                        ->options(
                            EmailTemplate::where('id', '!=', $this->record->id)
                                ->whereNotNull('settings->email_structure')
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                ])
                ->action('duplicateStructure'),

            Action::make('preview')
                ->label('Preview Email')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action('previewEmail'),

            Action::make('save')
                ->label('Save Design')
                ->icon('heroicon-o-check')
                ->action('saveStructure')
                ->color('success'),
        ];
    }

    public function saveStructure(): void
    {
        $settings = $this->record->settings ?? [];
        $settings['email_structure'] = $this->data;

        $this->record->update(['settings' => $settings]);

        Notification::make()
            ->title('Email design saved successfully')
            ->success()
            ->send();
    }

    public function addBlock(array $block): void
    {
        $this->data['blocks'][] = $block;
    }

    public function removeBlock(int $index): void
    {
        unset($this->data['blocks'][$index]);
        $this->data['blocks'] = array_values($this->data['blocks']);
    }

    public function updateBlock(int $index, array $updates): void
    {
        foreach ($updates as $key => $value) {
            $this->data['blocks'][$index][$key] = $value;
        }
    }

    public function reorderBlocks(array $order): void
    {
        $newOrder = [];
        foreach ($order as $index) {
            if (isset($this->data['blocks'][$index])) {
                $newOrder[] = $this->data['blocks'][$index];
            }
        }
        $this->data['blocks'] = $newOrder;
    }

    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->data['settings'][$key] = $value;
        }
    }

    public function duplicateStructure(array $data): void
    {
        $sourceTemplate = EmailTemplate::find($data['source_template_id']);

        if (!$sourceTemplate || !isset($sourceTemplate->settings['email_structure'])) {
            Notification::make()
                ->title('Email structure not found')
                ->danger()
                ->send();
            return;
        }

        $this->data = $sourceTemplate->settings['email_structure'];

        Notification::make()
            ->title('Email design duplicated successfully')
            ->body('Design copied from ' . $sourceTemplate->name . '. Click "Save Design" to apply.')
            ->success()
            ->send();
    }

    public function previewEmail(): void
    {
        // This will open a modal showing the preview
        $this->dispatch('open-email-preview', $this->data);
    }

    public function getBreadcrumbs(): array
    {
        return [
            EmailTemplateResource::getUrl('index') => 'Email Templates',
            EmailTemplateResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Email Builder',
        ];
    }
}
