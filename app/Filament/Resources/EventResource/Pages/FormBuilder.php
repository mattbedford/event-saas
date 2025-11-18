<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class FormBuilder extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.form-builder';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $title = 'Checkout Form Builder';

    public ?array $data = [];
    public Event $record;

    // Available field types
    public array $availableFields = [
        [
            'name' => 'full_name',
            'label' => 'Full Name',
            'type' => 'text',
            'default_required' => true,
            'removable' => false, // Core field, cannot be removed
        ],
        [
            'name' => 'email',
            'label' => 'Email Address',
            'type' => 'email',
            'default_required' => true,
            'removable' => false,
        ],
        [
            'name' => 'phone',
            'label' => 'Phone Number',
            'type' => 'tel',
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'company',
            'label' => 'Company',
            'type' => 'text',
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'job_title',
            'label' => 'Job Title',
            'type' => 'text',
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'dietary_requirements',
            'label' => 'Dietary Requirements',
            'type' => 'textarea',
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'special_needs',
            'label' => 'Special Needs / Accessibility',
            'type' => 'textarea',
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'attendee_type',
            'label' => 'Attendee Type',
            'type' => 'select',
            'options' => ['sponsor' => 'Sponsor', 'brand' => 'Brand', 'guest' => 'Guest'],
            'default_required' => false,
            'removable' => true,
        ],
        [
            'name' => 'custom_question_1',
            'label' => 'Custom Question 1',
            'type' => 'text',
            'placeholder' => 'Enter your custom question...',
            'default_required' => false,
            'removable' => true,
            'customizable' => true,
        ],
        [
            'name' => 'custom_question_2',
            'label' => 'Custom Question 2',
            'type' => 'textarea',
            'placeholder' => 'Enter your custom question...',
            'default_required' => false,
            'removable' => true,
            'customizable' => true,
        ],
    ];

    public function mount(): void
    {
        $this->record = $this->getRecord();

        // Load existing form configuration or set defaults
        $this->data = $this->record->settings['checkout_form'] ?? $this->getDefaultFormConfig();
    }

    protected function getDefaultFormConfig(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'full_name',
                    'label' => 'Full Name',
                    'type' => 'text',
                    'required' => true,
                    'enabled' => true,
                    'placeholder' => '',
                    'help_text' => '',
                ],
                [
                    'name' => 'email',
                    'label' => 'Email Address',
                    'type' => 'email',
                    'required' => true,
                    'enabled' => true,
                    'placeholder' => '',
                    'help_text' => '',
                ],
                [
                    'name' => 'company',
                    'label' => 'Company',
                    'type' => 'text',
                    'required' => false,
                    'enabled' => true,
                    'placeholder' => '',
                    'help_text' => '',
                ],
                [
                    'name' => 'phone',
                    'label' => 'Phone Number',
                    'type' => 'tel',
                    'required' => false,
                    'enabled' => false,
                    'placeholder' => '',
                    'help_text' => '',
                ],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label('Duplicate from Event')
                ->icon('heroicon-o-document-duplicate')
                ->form([
                    \Filament\Forms\Components\Select::make('source_event_id')
                        ->label('Copy form from')
                        ->options(
                            Event::where('id', '!=', $this->record->id)
                                ->whereNotNull('settings->checkout_form')
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                ])
                ->action('duplicateForm'),

            Action::make('reset')
                ->label('Reset to Default')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action('resetForm'),

            Action::make('save')
                ->label('Save Form')
                ->icon('heroicon-o-check')
                ->action('saveForm')
                ->color('success'),
        ];
    }

    public function saveForm(): void
    {
        $settings = $this->record->settings ?? [];
        $settings['checkout_form'] = $this->data;

        $this->record->update(['settings' => $settings]);

        Notification::make()
            ->title('Form configuration saved successfully')
            ->success()
            ->send();
    }

    public function addField(array $field): void
    {
        $this->data['fields'][] = [
            'name' => $field['name'],
            'label' => $field['label'],
            'type' => $field['type'],
            'required' => $field['default_required'] ?? false,
            'enabled' => true,
            'placeholder' => '',
            'help_text' => '',
            'options' => $field['options'] ?? null,
            'customizable' => $field['customizable'] ?? false,
        ];
    }

    public function removeField(int $index): void
    {
        // Check if field is removable
        $field = $this->data['fields'][$index] ?? null;

        if (!$field) {
            return;
        }

        // Find the field definition
        $fieldDef = collect($this->availableFields)->firstWhere('name', $field['name']);

        if ($fieldDef && !$fieldDef['removable']) {
            Notification::make()
                ->title('Cannot remove required field')
                ->body('This field is required for registration and cannot be removed.')
                ->danger()
                ->send();
            return;
        }

        unset($this->data['fields'][$index]);
        $this->data['fields'] = array_values($this->data['fields']);
    }

    public function updateField(int $index, array $updates): void
    {
        foreach ($updates as $key => $value) {
            $this->data['fields'][$index][$key] = $value;
        }
    }

    public function reorderFields(array $order): void
    {
        $newOrder = [];
        foreach ($order as $index) {
            if (isset($this->data['fields'][$index])) {
                $newOrder[] = $this->data['fields'][$index];
            }
        }
        $this->data['fields'] = $newOrder;
    }

    public function duplicateForm(array $data): void
    {
        $sourceEvent = Event::find($data['source_event_id']);

        if (!$sourceEvent || !isset($sourceEvent->settings['checkout_form'])) {
            Notification::make()
                ->title('Form configuration not found')
                ->danger()
                ->send();
            return;
        }

        $this->data = $sourceEvent->settings['checkout_form'];

        Notification::make()
            ->title('Form duplicated successfully')
            ->body('Form copied from ' . $sourceEvent->name . '. Click "Save Form" to apply.')
            ->success()
            ->send();
    }

    public function resetForm(): void
    {
        $this->data = $this->getDefaultFormConfig();

        Notification::make()
            ->title('Form reset to default')
            ->body('Click "Save Form" to apply changes.')
            ->success()
            ->send();
    }

    public function getBreadcrumbs(): array
    {
        return [
            EventResource::getUrl('index') => 'Events',
            EventResource::getUrl('edit', ['record' => $this->record]) => $this->record->name,
            '#' => 'Form Builder',
        ];
    }
}
