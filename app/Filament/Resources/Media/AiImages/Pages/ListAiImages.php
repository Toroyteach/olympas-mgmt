<?php

namespace App\Filament\Resources\Media\AiImages\Pages;

use App\Filament\Resources\Media\AiImages\AiImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiImages extends ListRecords
{
    protected static string $resource = AiImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Generate New Image'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AiImageStatsWidget::class,
        ];
    }
}
