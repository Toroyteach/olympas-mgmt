<?php

namespace App\Filament\Resources\Media\AiImages\Pages;

use App\Filament\Resources\Media\AiImages\AiImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiImage extends EditRecord
{
    protected static string $resource = AiImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
