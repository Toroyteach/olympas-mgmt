<?php

namespace App\Filament\Resources\Media\SocialAccounts\Pages;

use App\Filament\Resources\Media\SocialAccounts\SocialAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSocialAccount extends EditRecord
{
    protected static string $resource = SocialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
