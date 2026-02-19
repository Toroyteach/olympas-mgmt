<?php

namespace App\Filament\Resources\Media\SocialPosts\RelationManagers;

use App\Enums\DispatchStatus;
use App\Jobs\PublishSocialPostJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DispatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'dispatches';
    protected static ?string $title = 'Publishing Status';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account'),

                Tables\Columns\TextColumn::make('account.platform')
                    ->label('Platform')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DispatchStatus $state) => $state->label())
                    ->color(fn (DispatchStatus $state) => $state->color()),

                Tables\Columns\TextColumn::make('attempts'),

                Tables\Columns\TextColumn::make('error_message')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->canRetry())
                    ->action(function ($record) {
                        $record->update([
                            'status' => DispatchStatus::Pending,
                            'error_message' => null,
                        ]);
                        PublishSocialPostJob::dispatch($record);

                        Notification::make()->title('Retry dispatched')->success()->send();
                    }),
            ]);
    }
}
