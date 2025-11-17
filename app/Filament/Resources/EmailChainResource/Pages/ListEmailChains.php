<?php

namespace App\Filament\Resources\EmailChainResource\Pages;

use App\Filament\Resources\EmailChainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailChains extends ListRecords
{
    protected static string $resource = EmailChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
