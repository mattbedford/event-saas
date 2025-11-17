<?php

namespace App\Filament\Resources\EmailChainResource\Pages;

use App\Filament\Resources\EmailChainResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailChain extends EditRecord
{
    protected static string $resource = EmailChainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
