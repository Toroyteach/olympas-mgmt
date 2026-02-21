<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn(User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7C3AED&background=EDE9FE')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->description(fn(User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('primary')
                    ->separator(',')
                    ->searchable(),

                TextColumn::make('invitation_accepted_at')
                    ->label('Account Status')
                    ->formatStateUsing(function (User $record): string {
                        if ($record->invitation_accepted_at) {
                            return 'Setup Complete';
                        }
                        if ($record->invitation_sent_at) {
                            return 'Pending Invite';
                        }
                        return 'Not Invited';
                    })
                    ->badge()
                    ->color(function (User $record): string {
                        if ($record->invitation_accepted_at) {
                            return 'success';
                        }
                        if ($record->invitation_sent_at) {
                            return 'warning';
                        }
                        return 'gray';
                    }),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'inactive'  => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),

                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Role'),

                TernaryFilter::make('invitation_accepted_at')
                    ->label('Invitation Accepted')
                    ->nullable(),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
            ])
            ->actions([
                Action::make('resend_invitation')
                    ->label('Resend Invite')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn(User $record): bool => method_exists($record, 'isInvitationPending') && $record->isInvitationPending())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $token = Str::random(64);
                        $record->update([
                            'invitation_token'    => $token,
                            'invitation_sent_at'  => now(),
                        ]);
                        $record->notify(new UserInvitationNotification($record, config('app.url')));

                        Notification::make()
                            ->title('Invitation resent successfully.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn(User $record): bool => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update(['status' => 'suspended']);
                        Notification::make()->title('User suspended.')->warning()->send();
                    }),

                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record): bool => in_array($record->status, ['inactive', 'suspended']))
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update(['status' => 'active']);
                        Notification::make()->title('User activated.')->success()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('activate_selected')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'active'])),

                    BulkAction::make('suspend_selected')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'suspended'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}
