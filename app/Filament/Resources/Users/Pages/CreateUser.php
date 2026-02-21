<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Notifications\UserInvitationNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set a placeholder password; user will set their own via invitation
        $data['password'] = bcrypt(Str::random(32));

        // Generate invitation token
        $data['invitation_token']   = Str::random(64);
        $data['invitation_sent_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Sync direct permissions if provided
        if (! empty($this->data['permissions'])) {
            $record->syncPermissions($this->data['permissions']);
        }

        // Send invitation email
        $record->notify(new UserInvitationNotification($record, config('app.url')));

        Notification::make()
            ->title("Invitation sent to {$record->email}")
            ->body('The user will receive an email to set up their account.')
            ->success()
            ->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // We send a custom notification in afterCreate
    }
}
