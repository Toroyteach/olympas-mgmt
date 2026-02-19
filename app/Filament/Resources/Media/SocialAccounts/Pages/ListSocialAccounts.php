<?php

namespace App\Filament\Resources\Media\SocialAccounts\Pages;

use App\Filament\Resources\Media\SocialAccounts\SocialAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSocialAccounts extends ListRecords
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
