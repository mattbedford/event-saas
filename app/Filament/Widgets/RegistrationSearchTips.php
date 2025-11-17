<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RegistrationSearchTips extends Widget
{
    protected static string $view = 'filament.widgets.registration-search-tips';

    protected int | string | array $columnSpan = 'full';

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}
